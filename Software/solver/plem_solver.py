#!/usr/bin/env python3
# =============================================================================
# FARMAFÉ - Motor de Optimización PLEM (Programación Lineal Entera Mixta)
# Optimización lexicográfica en 2 etapas:
#   Etapa 1: Maximizar Nivel de Servicio Ponderado (S*)
#   Etapa 2: Minimizar Costo Logístico Total sujeto a S >= S* - epsilon
# =============================================================================

import sys
import json
import time
import math
import warnings
warnings.filterwarnings("ignore")

try:
    import pulp
except ImportError:
    print(json.dumps({
        "success": False,
        "status": "ERROR",
        "message": "La biblioteca PuLP no esta instalada en el entorno Python."
    }))
    sys.exit(1)


def solve_plem(data):
    start_time = time.time()
    
    # 1. Extraer configuracion
    scenario = data.get("scenario", {})
    horizon_months = int(scenario.get("horizon_months", 6))
    demand_mode = scenario.get("demand_mode", "AGREGADO")
    policy_shortage = scenario.get("policy_shortage", "BACKORDER") # BACKORDER o VENTA_PERDIDA
    budgets = scenario.get("budget_per_period", [250000.0] * horizon_months)
    epsilon = float(scenario.get("epsilon_tolerance", 0.0001))
    
    costs = data.get("costs", {})
    c_orden = float(costs.get("order_issue_bs", 50.0))
    c_posesion_anual = float(costs.get("holding_annual_pct", 20.0)) / 100.0
    c_posesion_mes = c_posesion_anual / 12.0
    c_faltante = float(costs.get("shortage_unit_bs", 15.0))
    c_baja = float(costs.get("expiration_penalty_bs", 5.0))
    c_despacho = float(costs.get("dispatch_shipment_bs", 35.0))
    
    products = data.get("products", [])
    suppliers = data.get("suppliers", [])
    product_supplier = data.get("product_supplier", [])
    initial_batches = data.get("initial_batches", [])
    demands = data.get("demand", [])
    committed_orders = data.get("committed_orders", [])
    
    # Mapeos rapidos
    prod_map = {p["id"]: p for p in products}
    periods = list(range(1, horizon_months + 1))
    
    # Pares producto-proveedor validos
    pp_valid = {}
    for pp in product_supplier:
        p_id = pp["product_id"]
        s_id = pp["supplier_id"]
        pp_valid[(p_id, s_id)] = {
            "cost_unit": float(pp.get("cost_unit", prod_map.get(p_id, {}).get("cost_avg", 1.0))),
            "lead_time_days": int(pp.get("lead_time_days", 7)),
            "min_order": int(pp.get("min_order", 1)),
            "pack_multiple": int(pp.get("pack_multiple", 1))
        }
        
    # Demanda D_it
    d_dict = {}
    for p_id in prod_map:
        for t in periods:
            d_dict[(p_id, t)] = 0
            
    for d in demands:
        p_id = d["product_id"]
        t = int(d["period"])
        if p_id in prod_map and 1 <= t <= horizon_months:
            d_dict[(p_id, t)] += int(d["quantity"])
            
    # Ponderaciones de servicio w_it
    # Se normalizan de modo que sum(w_it para d_ict > 0) == 1.0
    total_dem_positive = sum(1 for (p_id, t), dem in d_dict.items() if dem > 0)
    w_dict = {}
    if total_dem_positive > 0:
        for (p_id, t), dem in d_dict.items():
            if dem > 0:
                # Prioridad por clasificacion ABC
                abc = prod_map[p_id].get("abc", "B")
                factor = 3.0 if abc == 'A' else (2.0 if abc == 'B' else 1.0)
                w_dict[(p_id, t)] = factor
        w_sum = sum(w_dict.values())
        for k in w_dict:
            w_dict[k] /= w_sum
    else:
        for (p_id, t) in d_dict:
            w_dict[(p_id, t)] = 0.0

    # =========================================================================
    # CONSTRUCCION DEL MODELO MATEMATICO (PuLP / HiGHS / CBC)
    # =========================================================================
    prob = pulp.LpProblem("FARMAFE_PLEM_MultiPeriodo", pulp.LpMaximize)
    
    # Variables de decision de compra x_ist y z_ist
    x_var = {}
    z_var = {}
    n_var = {} # para multiplos de empaque
    
    for (p_id, s_id), pp_info in pp_valid.items():
        pack = pp_info["pack_multiple"]
        mo = pp_info["min_order"]
        c_u = pp_info["cost_unit"]
        
        for t in periods:
            b_t = budgets[t-1] if t-1 < len(budgets) else 250000.0
            m_upper = max(mo, int(b_t / max(0.01, c_u)) + 100)
            
            z_var[(p_id, s_id, t)] = pulp.LpVariable(
                f"z_{p_id}_{s_id}_{t}", cat=pulp.LpBinary
            )
            
            if pack > 1:
                n_var[(p_id, s_id, t)] = pulp.LpVariable(
                    f"n_{p_id}_{s_id}_{t}", lowBound=0, cat=pulp.LpInteger
                )
                x_var[(p_id, s_id, t)] = pack * n_var[(p_id, s_id, t)]
            else:
                x_var[(p_id, s_id, t)] = pulp.LpVariable(
                    f"x_{p_id}_{s_id}_{t}", lowBound=0, cat=pulp.LpInteger
                )
                
            # Restriccion pedido minimo y Big-M de activacion
            prob += x_var[(p_id, s_id, t)] >= mo * z_var[(p_id, s_id, t)], f"MinOrder_{p_id}_{s_id}_{t}"
            prob += x_var[(p_id, s_id, t)] <= m_upper * z_var[(p_id, s_id, t)], f"BigM_{p_id}_{s_id}_{t}"
            
    # Fijar decisiones de compras comprometidas
    for comm in committed_orders:
        p_id = comm.get("product_id")
        s_id = comm.get("supplier_id")
        t = int(comm.get("period", 1))
        q = int(comm.get("quantity", 0))
        if (p_id, s_id, t) in x_var:
            prob += x_var[(p_id, s_id, t)] == q, f"Committed_x_{p_id}_{s_id}_{t}"
            prob += z_var[(p_id, s_id, t)] == (1 if q > 0 else 0), f"Committed_z_{p_id}_{s_id}_{t}"

    # Variables de demanda atendida A_it, pendiente p_it y perdida q_it
    a_var = {}
    p_var = {}
    q_var = {}
    
    for p_id in prod_map:
        for t in periods:
            d_val = d_dict[(p_id, t)]
            a_var[(p_id, t)] = pulp.LpVariable(f"A_{p_id}_{t}", lowBound=0, cat=pulp.LpInteger)
            
            if policy_shortage == "BACKORDER":
                p_var[(p_id, t)] = pulp.LpVariable(f"p_{p_id}_{t}", lowBound=0, cat=pulp.LpInteger)
                # Conservacion de demanda con backorder
                if t == 1:
                    prob += a_var[(p_id, t)] + p_var[(p_id, t)] == d_val, f"DemCons_{p_id}_{t}"
                else:
                    prob += a_var[(p_id, t)] + p_var[(p_id, t)] == d_val + p_var[(p_id, t-1)], f"DemCons_{p_id}_{t}"
            else: # VENTA_PERDIDA
                q_var[(p_id, t)] = pulp.LpVariable(f"q_{p_id}_{t}", lowBound=0, cat=pulp.LpInteger)
                prob += a_var[(p_id, t)] + q_var[(p_id, t)] == d_val, f"DemCons_{p_id}_{t}"
                
            # Restriccion fundamental del contrato: 0 <= A_it <= D_it
            prob += a_var[(p_id, t)] <= d_val, f"MaxDemand_{p_id}_{t}"
            prob += a_var[(p_id, t)] >= 0, f"NonNegDemand_{p_id}_{t}"
            
    # Lotes existentes y sus balances
    # Lotes: l in L_i
    # Balance: I_ilt = I_il,t-1 + R_ilt - g_ilt - W_ilt
    g_var = {} # Asignacion de lote a demanda
    inv_var = {} # Saldo de lote
    w_var = {} # Baja por vencimiento
    
    batches_by_prod = {}
    for b in initial_batches:
        p_id = b["product_id"]
        batches_by_prod.setdefault(p_id, []).append(b)
        
    for p_id in prod_map:
        b_list = batches_by_prod.get(p_id, [])
        for b in b_list:
            b_id = b["id"]
            init_stock = int(b.get("initial_stock", 0))
            exp_p = int(b.get("expire_period", horizon_months + 12))
            
            for t in periods:
                g_var[(b_id, t)] = pulp.LpVariable(f"g_{b_id}_{t}", lowBound=0, cat=pulp.LpInteger)
                inv_var[(b_id, t)] = pulp.LpVariable(f"I_{b_id}_{t}", lowBound=0, cat=pulp.LpInteger)
                w_var[(b_id, t)] = pulp.LpVariable(f"W_{b_id}_{t}", lowBound=0, cat=pulp.LpInteger)
                
                # Regla de vencimiento: si t > exp_p, el lote no es elegible
                if t > exp_p:
                    prob += g_var[(b_id, t)] == 0, f"NoEligible_{b_id}_{t}"
                    prob += inv_var[(b_id, t)] == 0, f"ZeroInvExpired_{b_id}_{t}"
                    prob += w_var[(b_id, t)] == 0, f"ZeroWExpired_{b_id}_{t}"
                elif t == exp_p:
                    # En el periodo de vencimiento, todo saldo remanente debe darse de baja
                    if t == 1:
                        prob += w_var[(b_id, t)] == init_stock - g_var[(b_id, t)], f"ExpireBaja_{b_id}_{t}"
                    else:
                        prob += w_var[(b_id, t)] == inv_var[(b_id, t-1)] - g_var[(b_id, t)], f"ExpireBaja_{b_id}_{t}"
                    prob += inv_var[(b_id, t)] == 0, f"InvZeroAtExp_{b_id}_{t}"
                else: # t < exp_p
                    prob += w_var[(b_id, t)] == 0, f"NoExpireYet_{b_id}_{t}"
                    if t == 1:
                        prob += inv_var[(b_id, t)] == init_stock - g_var[(b_id, t)], f"BalInv_{b_id}_{t}"
                    else:
                        prob += inv_var[(b_id, t)] == inv_var[(b_id, t-1)] - g_var[(b_id, t)], f"BalInv_{b_id}_{t}"

    # Lotes proyectados generados por compras:
    # Cada par (p_id, s_id, t) que llega en t_arr <= horizon_months se convierte en saldo utilizable
    purch_arr_vars = {} # (p_id, t_arr)
    for (p_id, s_id), pp_info in pp_valid.items():
        lt_days = pp_info["lead_time_days"]
        lt_months = math.ceil(lt_days / 30.0) # Periodos de lead time
        for t in periods:
            t_arr = t + lt_months
            if t_arr <= horizon_months:
                purch_arr_vars.setdefault((p_id, t_arr), []).append(x_var[(p_id, s_id, t)])
                
    # Balance de compras recibidas asignables a demanda
    purch_stock_var = {}
    purch_alloc_var = {}
    for p_id in prod_map:
        for t in periods:
            purch_stock_var[(p_id, t)] = pulp.LpVariable(f"I_purch_{p_id}_{t}", lowBound=0, cat=pulp.LpInteger)
            purch_alloc_var[(p_id, t)] = pulp.LpVariable(f"g_purch_{p_id}_{t}", lowBound=0, cat=pulp.LpInteger)
            
            arrivals = purch_arr_vars.get((p_id, t), [])
            sum_arr = pulp.lpSum(arrivals) if arrivals else 0
            
            if t == 1:
                prob += purch_stock_var[(p_id, t)] == sum_arr - purch_alloc_var[(p_id, t)], f"BalPurchStock_{p_id}_{t}"
            else:
                prob += purch_stock_var[(p_id, t)] == purch_stock_var[(p_id, t-1)] + sum_arr - purch_alloc_var[(p_id, t)], f"BalPurchStock_{p_id}_{t}"

    # Relacion de asignaciones totales con la demanda atendida A_it
    for p_id in prod_map:
        for t in periods:
            b_list = batches_by_prod.get(p_id, [])
            alloc_terms = [g_var[(b["id"], t)] for b in b_list]
            alloc_terms.append(purch_alloc_var[(p_id, t)])
            prob += a_var[(p_id, t)] == pulp.lpSum(alloc_terms), f"DemandAllocLink_{p_id}_{t}"

    # Restricciones presupuestarias mensuales
    for t in periods:
        b_limit = budgets[t-1] if t-1 < len(budgets) else 250000.0
        spend_terms = []
        for (p_id, s_id), pp_info in pp_valid.items():
            spend_terms.append(pp_info["cost_unit"] * x_var[(p_id, s_id, t)])
        prob += pulp.lpSum(spend_terms) <= b_limit, f"Presupuesto_Mes_{t}"

    # =========================================================================
    # ETAPA 1: MAXIMIZAR NIVEL DE SERVICIO PONDERADO
    # =========================================================================
    service_terms = []
    for (p_id, t), dem in d_dict.items():
        if dem > 0:
            w_it = w_dict.get((p_id, t), 0.0)
            service_terms.append(w_it * (a_var[(p_id, t)] / float(dem)))
            
    service_obj = pulp.lpSum(service_terms) if service_terms else 0
    prob.setObjective(service_obj)
    
    # Solver
    solver = pulp.PULP_CBC_CMD(msg=0, timeLimit=120)
    prob.solve(solver)
    
    status_s1 = pulp.LpStatus[prob.status]
    if status_s1 != "Optimal":
        return {
            "success": False,
            "status": status_s1.upper(),
            "stage1_status": status_s1,
            "stage2_status": "NOT_EXECUTED",
            "message": f"La etapa 1 no alcanzo optimalidad: {status_s1}",
            "execution_time_seconds": round(time.time() - start_time, 3)
        }
        
    s_opt = float(pulp.value(prob.objective) or 0.0)

    # =========================================================================
    # ETAPA 2: MINIMIZAR COSTO LOGISTICO CONSERVANDO S* >= S_opt - epsilon
    # =========================================================================
    # Agregar restriccion de servicio optimo
    s_target = max(0.0, s_opt - epsilon)
    prob += service_obj >= s_target, "Preservar_Servicio_Optimo"
    
    # Costos
    c_compra_terms = []
    c_orden_terms = []
    for (p_id, s_id), pp_info in pp_valid.items():
        c_u = pp_info["cost_unit"]
        for t in periods:
            c_compra_terms.append(c_u * x_var[(p_id, s_id, t)])
            c_orden_terms.append(c_orden * z_var[(p_id, s_id, t)])
            
    c_pos_terms = []
    c_baja_terms = []
    for p_id in prod_map:
        c_u_prod = float(prod_map[p_id].get("cost_avg", 1.0))
        b_list = batches_by_prod.get(p_id, [])
        for b in b_list:
            b_id = b["id"]
            for t in periods:
                c_pos_terms.append(c_posesion_mes * c_u_prod * inv_var[(b_id, t)])
                c_baja_terms.append((c_u_prod + c_baja) * w_var[(b_id, t)])
        for t in periods:
            c_pos_terms.append(c_posesion_mes * c_u_prod * purch_stock_var[(p_id, t)])
            
    c_falt_terms = []
    for p_id in prod_map:
        for t in periods:
            if policy_shortage == "BACKORDER":
                c_falt_terms.append(c_faltante * p_var[(p_id, t)])
            else:
                c_falt_terms.append(c_faltante * q_var[(p_id, t)])
                
    total_cost_expr = (
        pulp.lpSum(c_compra_terms) +
        pulp.lpSum(c_orden_terms) +
        pulp.lpSum(c_pos_terms) +
        pulp.lpSum(c_baja_terms) +
        pulp.lpSum(c_falt_terms)
    )
    
    prob.sense = pulp.LpMinimize
    prob.setObjective(total_cost_expr)
    
    prob.solve(solver)
    status_s2 = pulp.LpStatus[prob.status]
    
    if status_s2 != "Optimal":
        return {
            "success": False,
            "status": status_s2.upper(),
            "stage1_status": status_s1,
            "stage2_status": status_s2,
            "service_level_s1": s_opt,
            "message": f"La etapa 2 no alcanzo optimalidad: {status_s2}",
            "execution_time_seconds": round(time.time() - start_time, 3)
        }
        
    s_achieved = float(pulp.value(service_obj) or 0.0)
    c_total = float(pulp.value(total_cost_expr) or 0.0)
    c_compra_val = float(pulp.value(pulp.lpSum(c_compra_terms)) or 0.0)
    c_orden_val = float(pulp.value(pulp.lpSum(c_orden_terms)) or 0.0)
    c_pos_val = float(pulp.value(pulp.lpSum(c_pos_terms)) or 0.0)
    c_baja_val = float(pulp.value(pulp.lpSum(c_baja_terms)) or 0.0)
    c_falt_val = float(pulp.value(pulp.lpSum(c_falt_terms)) or 0.0)
    
    # =========================================================================
    # CONSTRUIR RESULTADOS DETALLADOS
    # =========================================================================
    purchases_rec = []
    total_purch_units = 0
    for (p_id, s_id), pp_info in pp_valid.items():
        lt_days = pp_info["lead_time_days"]
        lt_m = math.ceil(lt_days / 30.0)
        c_u = pp_info["cost_unit"]
        for t in periods:
            q_val = int(round(pulp.value(x_var[(p_id, s_id, t)]) or 0))
            if q_val > 0:
                total_purch_units += q_val
                purchases_rec.append({
                    "period_t": t,
                    "period_name": f"Mes {t}",
                    "product_id": p_id,
                    "product_code": prod_map[p_id]["code"],
                    "product_desc": prod_map[p_id]["description"],
                    "supplier_id": s_id,
                    "quantity": q_val,
                    "cost_unit": c_u,
                    "subtotal": round(q_val * c_u, 2),
                    "lead_time_days": lt_days,
                    "arrival_period": t + lt_m,
                    "status": "PROPUESTA"
                })
                
    demand_res = []
    tot_req = 0
    tot_att = 0
    tot_pend = 0
    tot_lost = 0
    for p_id in prod_map:
        for t in periods:
            d_val = d_dict[(p_id, t)]
            a_val = int(round(pulp.value(a_var[(p_id, t)]) or 0))
            p_val = int(round(pulp.value(p_var[(p_id, t)]) or 0)) if policy_shortage == "BACKORDER" else 0
            q_val = int(round(pulp.value(q_var[(p_id, t)]) or 0)) if policy_shortage == "VENTA_PERDIDA" else 0
            
            tot_req += d_val
            tot_att += a_val
            tot_pend += p_val
            tot_lost += q_val
            
            pct = round((a_val / d_val * 100.0), 2) if d_val > 0 else 100.0
            demand_res.append({
                "period_t": t,
                "period_name": f"Mes {t}",
                "product_id": p_id,
                "product_code": prod_map[p_id]["code"],
                "product_desc": prod_map[p_id]["description"],
                "requested": d_val,
                "fulfilled": a_val,
                "pending": p_val,
                "lost": q_val,
                "fulfillment_pct": pct
            })
            
    # Proyeccion de inventario por lote y periodo
    inv_proj = []
    for p_id in prod_map:
        b_list = batches_by_prod.get(p_id, [])
        for b in b_list:
            b_id = b["id"]
            b_code = b.get("code", f"L-{b_id}")
            for t in periods:
                init_s = int(b.get("initial_stock", 0)) if t == 1 else int(round(pulp.value(inv_var[(b_id, t-1)]) or 0))
                g_val = int(round(pulp.value(g_var[(b_id, t)]) or 0))
                w_val = int(round(pulp.value(w_var[(b_id, t)]) or 0))
                fin_s = int(round(pulp.value(inv_var[(b_id, t)]) or 0))
                
                inv_proj.append({
                    "period_t": t,
                    "period_name": f"Mes {t}",
                    "product_id": p_id,
                    "batch_id": b_id,
                    "batch_code": b_code,
                    "is_projected": False,
                    "initial_stock": init_s,
                    "incoming": 0,
                    "allocated": g_val,
                    "expired": w_val,
                    "final_stock": fin_s
                })
                
        # Inventario de compras proyectadas
        for t in periods:
            init_purch = 0 if t == 1 else int(round(pulp.value(purch_stock_var[(p_id, t-1)]) or 0))
            arrs = purch_arr_vars.get((p_id, t), [])
            incoming_q = sum(int(round(pulp.value(v) or 0)) for v in arrs)
            alloc_purch = int(round(pulp.value(purch_alloc_var[(p_id, t)]) or 0))
            fin_purch = int(round(pulp.value(purch_stock_var[(p_id, t)]) or 0))
            
            if incoming_q > 0 or init_purch > 0 or alloc_purch > 0 or fin_purch > 0:
                inv_proj.append({
                    "period_t": t,
                    "period_name": f"Mes {t}",
                    "product_id": p_id,
                    "batch_id": None,
                    "batch_code": f"PROY-COMPRAS-M{t}",
                    "is_projected": True,
                    "initial_stock": init_purch,
                    "incoming": incoming_q,
                    "allocated": alloc_purch,
                    "expired": 0,
                    "final_stock": fin_purch
                })
                
    elapsed = round(time.time() - start_time, 3)
    
    return {
        "success": True,
        "status": "OPTIMAL",
        "solver": "PuLP / CBC-HiGHS",
        "version_solver": pulp.__version__,
        "stage1_status": status_s1,
        "stage2_status": status_s2,
        "service_level_s1": round(s_opt, 6),
        "service_level_s2": round(s_achieved, 6),
        "total_cost_s2": round(c_total, 2),
        "cost_breakdown": {
            "compra_bs": round(c_compra_val, 2),
            "orden_bs": round(c_orden_val, 2),
            "posesion_bs": round(c_pos_val, 2),
            "faltante_bs": round(c_falt_val, 2),
            "vencimiento_bs": round(c_baja_val, 2),
            "despacho_bs": 0.0
        },
        "summary": {
            "total_demand_requested": tot_req,
            "total_demand_fulfilled": tot_att,
            "total_demand_pending": tot_pend,
            "total_demand_lost": tot_lost,
            "total_purchases_units": total_purch_units,
            "global_service_pct": round((tot_att / tot_req * 100.0), 2) if tot_req > 0 else 100.0
        },
        "purchases_recommended": purchases_rec,
        "demand_results": demand_res,
        "inventory_projection": inv_proj,
        "execution_time_seconds": elapsed,
        "diagnostics": f"Optimizacion lexicografica exitosa en {elapsed}s. Nivel de servicio S*={round(s_opt*100, 2)}% preservado con tolerancia epsilon={epsilon}."
    }


def main():
    if len(sys.argv) > 1 and sys.argv[1] != "-":
        input_file = sys.argv[1]
        with open(input_file, "r", encoding="utf-8") as f:
            data = json.load(f)
    else:
        data = json.load(sys.stdin)
        
    result = solve_plem(data)
    print(json.dumps(result, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
