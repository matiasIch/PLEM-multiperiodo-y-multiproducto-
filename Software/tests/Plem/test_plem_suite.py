#!/usr/bin/env python3
# =============================================================================
# FARMAFÉ - Suite de Pruebas Matemáticas y Verificación del Solver PLEM
# Comprueba los 10 casos mínimos requeridos por el Contrato Matemático
# =============================================================================

import os
import sys
import json
import unittest

# Importar el modulo del solver
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', 'solver')))
from plem_solver import solve_plem


class TestPlemSuite(unittest.TestCase):

    def test_01_caso_pequeno_verificable_manualmente(self):
        """Caso 1: 2 medicamentos, 1 proveedor, 2 periodos.
           Med 1: Stock=100, Demanda=(150, 100). Necesita comprar 150.
           Med 2: Stock=200, Demanda=(100, 100). Stock suficiente (200=200), compra 0.
        """
        payload = {
            "scenario": {
                "horizon_months": 2,
                "demand_mode": "AGREGADO",
                "policy_shortage": "BACKORDER",
                "budget_per_period": [50000.0, 50000.0],
                "epsilon_tolerance": 0.0001
            },
            "costs": {
                "order_issue_bs": 50.0,
                "holding_annual_pct": 20.0,
                "shortage_unit_bs": 15.0,
                "expiration_penalty_bs": 5.0
            },
            "products": [
                {"id": 1, "code": "MED-01", "description": "Med 1", "cost_avg": 10.0, "abc": "A"},
                {"id": 2, "code": "MED-02", "description": "Med 2", "cost_avg": 5.0, "abc": "B"}
            ],
            "suppliers": [
                {"id": 1, "code": "PRV-01", "name": "Proveedor Test"}
            ],
            "product_supplier": [
                {"product_id": 1, "supplier_id": 1, "cost_unit": 10.0, "lead_time_days": 0, "min_order": 1, "pack_multiple": 1},
                {"product_id": 2, "supplier_id": 1, "cost_unit": 5.0, "lead_time_days": 0, "min_order": 1, "pack_multiple": 1}
            ],
            "initial_batches": [
                {"id": 101, "product_id": 1, "initial_stock": 100, "expire_period": 5},
                {"id": 102, "product_id": 2, "initial_stock": 200, "expire_period": 5}
            ],
            "demand": [
                {"product_id": 1, "period": 1, "quantity": 150},
                {"product_id": 1, "period": 2, "quantity": 100},
                {"product_id": 2, "period": 1, "quantity": 100},
                {"product_id": 2, "period": 2, "quantity": 100}
            ]
        }
        res = solve_plem(payload)
        self.assertTrue(res["success"], "El solver debio ser exitoso")
        self.assertEqual(res["status"], "OPTIMAL")
        self.assertAlmostEqual(res["service_level_s1"], 1.0, places=4, msg="El servicio debe ser 100%")
        # Compras recomendadas: para Med 2 debe ser 0 compras
        purchases_med2 = [p for p in res["purchases_recommended"] if p["product_id"] == 2]
        self.assertEqual(len(purchases_med2), 0, "No debe comprar Med 2 pues tiene stock suficiente")

    def test_02_inventario_suficiente_sin_compras_innecesarias(self):
        """Caso 2: Stock inicial de 1000, demanda total 400. Compras deben ser 0."""
        payload = {
            "scenario": {"horizon_months": 2, "budget_per_period": [10000.0, 10000.0]},
            "costs": {"order_issue_bs": 50.0},
            "products": [{"id": 1, "code": "M1", "description": "M1", "cost_avg": 2.0}],
            "suppliers": [{"id": 1, "code": "S1", "name": "S1"}],
            "product_supplier": [{"product_id": 1, "supplier_id": 1, "cost_unit": 2.0, "lead_time_days": 1, "min_order": 10, "pack_multiple": 1}],
            "initial_batches": [{"id": 1, "product_id": 1, "initial_stock": 1000, "expire_period": 6}],
            "demand": [{"product_id": 1, "period": 1, "quantity": 200}, {"product_id": 1, "period": 2, "quantity": 200}]
        }
        res = solve_plem(payload)
        self.assertTrue(res["success"])
        self.assertEqual(res["summary"]["total_purchases_units"], 0, "Compras deben ser 0")
        self.assertEqual(res["summary"]["global_service_pct"], 100.0)

    def test_03_presupuesto_restrictivo(self):
        """Caso 3: Presupuesto de solo 100 Bs en mes 1. Costo unitario 10 Bs.
           Demanda 50 unidades (requeriria 500 Bs). Solo puede comprar maximo 10 unidades."""
        payload = {
            "scenario": {"horizon_months": 1, "budget_per_period": [100.0]},
            "costs": {"order_issue_bs": 0.0},
            "products": [{"id": 1, "code": "M1", "description": "M1", "cost_avg": 10.0}],
            "suppliers": [{"id": 1, "code": "S1", "name": "S1"}],
            "product_supplier": [{"product_id": 1, "supplier_id": 1, "cost_unit": 10.0, "lead_time_days": 0, "min_order": 1, "pack_multiple": 1}],
            "initial_batches": [{"id": 1, "product_id": 1, "initial_stock": 0, "expire_period": 6}],
            "demand": [{"product_id": 1, "period": 1, "quantity": 50}]
        }
        res = solve_plem(payload)
        self.assertTrue(res["success"])
        # Verificamos que el gasto no exceda 100 Bs
        total_spent = sum(p["subtotal"] for p in res["purchases_recommended"])
        self.assertLessEqual(total_spent, 100.0, "El gasto no puede superar el presupuesto disponible")

    def test_04_lote_vencido_no_elegible(self):
        """Caso 4: Lote vence en periodo 1 (expire_period=1). En periodo 2 no debe atenderse con ese lote."""
        payload = {
            "scenario": {"horizon_months": 2, "budget_per_period": [0.0, 0.0]}, # sin presupuesto de compra
            "costs": {"expiration_penalty_bs": 5.0},
            "products": [{"id": 1, "code": "M1", "description": "M1", "cost_avg": 5.0}],
            "suppliers": [{"id": 1, "code": "S1", "name": "S1"}],
            "product_supplier": [{"product_id": 1, "supplier_id": 1, "cost_unit": 5.0, "lead_time_days": 0, "min_order": 1, "pack_multiple": 1}],
            "initial_batches": [{"id": 10, "product_id": 1, "initial_stock": 50, "expire_period": 1}],
            "demand": [{"product_id": 1, "period": 1, "quantity": 10}, {"product_id": 1, "period": 2, "quantity": 30}]
        }
        res = solve_plem(payload)
        self.assertTrue(res["success"])
        # En periodo 2, el lote expiro al final del periodo 1, por lo que las 40 restantes se dieron de baja
        # y no pueden atender demanda del mes 2.
        dem_m2 = [d for d in res["demand_results"] if d["period_t"] == 2][0]
        self.assertEqual(dem_m2["fulfilled"], 0, "No debe atenderse demanda en mes 2 con lote vencido")

    def test_05_compra_minima_y_empaque(self):
        """Caso 5: Pedido minimo = 50, Multiplo de empaque = 20.
           Demanda = 15 unidades. Debe comprar al menos max(50, ceil(50/20)*20) = 60 unidades."""
        payload = {
            "scenario": {"horizon_months": 1, "budget_per_period": [5000.0]},
            "costs": {"order_issue_bs": 50.0},
            "products": [{"id": 1, "code": "M1", "description": "M1", "cost_avg": 2.0}],
            "suppliers": [{"id": 1, "code": "S1", "name": "S1"}],
            "product_supplier": [{"product_id": 1, "supplier_id": 1, "cost_unit": 2.0, "lead_time_days": 0, "min_order": 50, "pack_multiple": 20}],
            "initial_batches": [{"id": 1, "product_id": 1, "initial_stock": 0, "expire_period": 6}],
            "demand": [{"product_id": 1, "period": 1, "quantity": 15}]
        }
        res = solve_plem(payload)
        self.assertTrue(res["success"])
        purch = res["purchases_recommended"][0]
        self.assertGreaterEqual(purch["quantity"], 50, "Debe respetar la compra minima de 50")
        self.assertEqual(purch["quantity"] % 20, 0, "Debe ser multiplo del empaque (20)")
        self.assertEqual(purch["quantity"], 60, "Debe comprar exactamente 60 unidades")

    def test_06_orden_comprometida_congelada(self):
        """Caso 6: Orden de compra comprometida en periodo 1 con cantidad fija 80.
           El modelo debe respetar exactamente x_ist = 80 sin alterarla."""
        payload = {
            "scenario": {"horizon_months": 2, "budget_per_period": [5000.0, 5000.0]},
            "costs": {"order_issue_bs": 10.0},
            "products": [{"id": 1, "code": "M1", "description": "M1", "cost_avg": 5.0}],
            "suppliers": [{"id": 1, "code": "S1", "name": "S1"}],
            "product_supplier": [{"product_id": 1, "supplier_id": 1, "cost_unit": 5.0, "lead_time_days": 0, "min_order": 1, "pack_multiple": 1}],
            "initial_batches": [{"id": 1, "product_id": 1, "initial_stock": 0, "expire_period": 6}],
            "demand": [{"product_id": 1, "period": 1, "quantity": 30}, {"product_id": 1, "period": 2, "quantity": 30}],
            "committed_orders": [
                {"product_id": 1, "supplier_id": 1, "period": 1, "quantity": 80}
            ]
        }
        res = solve_plem(payload)
        self.assertTrue(res["success"])
        p_m1 = [p for p in res["purchases_recommended"] if p["period_t"] == 1][0]
        self.assertEqual(p_m1["quantity"], 80, "La orden comprometida debe mantenerse en 80 unidades")

    def test_07_segunda_etapa_minimiza_costos(self):
        """Caso 7: Dos proveedores para el mismo producto:
           Proveedor A: Costo 12 Bs.
           Proveedor B: Costo 8 Bs.
           Ambos cumplen el servicio al 100%, pero la etapa 2 debe elegir el Proveedor B para minimizar costo."""
        payload = {
            "scenario": {"horizon_months": 1, "budget_per_period": [10000.0]},
            "costs": {"order_issue_bs": 10.0},
            "products": [{"id": 1, "code": "M1", "description": "M1", "cost_avg": 10.0}],
            "suppliers": [
                {"id": 1, "code": "PRV-CARO", "name": "Caro"},
                {"id": 2, "code": "PRV-BARATO", "name": "Barato"}
            ],
            "product_supplier": [
                {"product_id": 1, "supplier_id": 1, "cost_unit": 12.0, "lead_time_days": 0, "min_order": 1, "pack_multiple": 1},
                {"product_id": 1, "supplier_id": 2, "cost_unit": 8.0, "lead_time_days": 0, "min_order": 1, "pack_multiple": 1}
            ],
            "initial_batches": [{"id": 1, "product_id": 1, "initial_stock": 0, "expire_period": 6}],
            "demand": [{"product_id": 1, "period": 1, "quantity": 100}]
        }
        res = solve_plem(payload)
        self.assertTrue(res["success"])
        self.assertEqual(res["summary"]["global_service_pct"], 100.0)
        purch = res["purchases_recommended"][0]
        self.assertEqual(purch["supplier_id"], 2, "Debe seleccionar el proveedor mas economico (id 2)")
        self.assertEqual(purch["cost_unit"], 8.0)

    def test_08_lead_time_impide_uso_inmediato(self):
        """Caso 8: Lead time de 35 dias (llega en t+2). Demanda en mes 1 no puede ser
           atendida con la compra emitida en mes 1."""
        payload = {
            "scenario": {"horizon_months": 2, "budget_per_period": [5000.0, 5000.0]},
            "costs": {"order_issue_bs": 10.0},
            "products": [{"id": 1, "code": "M1", "description": "M1", "cost_avg": 5.0}],
            "suppliers": [{"id": 1, "code": "S1", "name": "S1"}],
            "product_supplier": [{"product_id": 1, "supplier_id": 1, "cost_unit": 5.0, "lead_time_days": 35, "min_order": 1, "pack_multiple": 1}],
            "initial_batches": [{"id": 1, "product_id": 1, "initial_stock": 0, "expire_period": 6}],
            "demand": [{"product_id": 1, "period": 1, "quantity": 50}, {"product_id": 1, "period": 2, "quantity": 0}]
        }
        res = solve_plem(payload)
        self.assertTrue(res["success"])
        # En mes 1, la compra llega en mes 1 + ceil(35/30) = 3 (fuera del horizonte o no en mes 1)
        dem_m1 = [d for d in res["demand_results"] if d["period_t"] == 1][0]
        self.assertEqual(dem_m1["fulfilled"], 0, "No debe atender demanda en mes 1 si lead time retrasa la llegada")

    def test_09_caso_infactible_reportado_claramente(self):
        """Caso 9: Restricciones en conflicto directo (ej. compra comprometida > presupuesto disponible).
           Debe reportar INFEASIBLE y no simular un optimo falso."""
        payload = {
            "scenario": {"horizon_months": 1, "budget_per_period": [50.0]}, # Presupuesto 50 Bs
            "costs": {},
            "products": [{"id": 1, "code": "M1", "description": "M1", "cost_avg": 10.0}],
            "suppliers": [{"id": 1, "code": "S1", "name": "S1"}],
            "product_supplier": [{"product_id": 1, "supplier_id": 1, "cost_unit": 10.0, "lead_time_days": 0, "min_order": 1, "pack_multiple": 1}],
            "initial_batches": [],
            "demand": [{"product_id": 1, "period": 1, "quantity": 10}],
            "committed_orders": [
                {"product_id": 1, "supplier_id": 1, "period": 1, "quantity": 100} # Exige 1000 Bs > 50 Bs
            ]
        }
        res = solve_plem(payload)
        self.assertFalse(res["success"], "El caso debe fallar o ser infactible")
        self.assertIn(res["status"], ["INFEASIBLE", "ERROR"], "Debe reportar estado INFEASIBLE")


if __name__ == "__main__":
    unittest.main()
