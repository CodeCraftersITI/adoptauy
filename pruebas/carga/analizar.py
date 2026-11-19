# Analiza los resultados de la prueba de carga CP-38 (archivo .jtl de JMeter) y dice si se
# cumplen RNF:0001, RNF:0004, RNF:0005 y RNF:0006:
#   - percentil 95 menor a 2 segundos en las consultas y en el inicio de sesión
#   - 0 errores del servidor (5xx) durante toda la prueba
# Uso:  python pruebas/carga/analizar.py pruebas/carga/resultados/cp38.jtl
import csv
import sys

LIMITE_MS = 2000
INICIO_DE_SESION = '02 Inicio de sesión'


def percentil_95(valores):
    valores = sorted(valores)
    posicion = max(0, int(round(0.95 * len(valores))) - 1)
    return valores[posicion]


filas = list(csv.DictReader(open(sys.argv[1], encoding='utf-8')))
inicio = min(int(f['timeStamp']) for f in filas)
fin = max(int(f['timeStamp']) + int(f['elapsed']) for f in filas)
usuarios = max(int(f['allThreads']) for f in filas)

print('Prueba de carga CP-38')
print('Duración: %.1f minutos · usuarios concurrentes: %d · pedidos: %d' % ((fin - inicio) / 60000, usuarios, len(filas)))
print()
print('%-48s %8s %8s %8s %8s' % ('Pedido', 'Cant.', 'Media', 'P95', 'Errores'))

por_pedido = {}
for f in filas:
    por_pedido.setdefault(f['label'], []).append(f)

cumple = True
for nombre in sorted(por_pedido):
    lista = por_pedido[nombre]
    tiempos = [int(f['elapsed']) for f in lista]
    errores = sum(1 for f in lista if f['success'] != 'true')
    p95 = percentil_95(tiempos)
    marca = '' if p95 < LIMITE_MS else '  <- supera 2 s'
    if p95 >= LIMITE_MS:
        cumple = False
    print('%-48s %8d %6d ms %6d ms %8d%s' % (nombre, len(lista), sum(tiempos) / len(tiempos), p95, errores, marca))

todos = [int(f['elapsed']) for f in filas]
errores_5xx = sum(1 for f in filas if f['responseCode'].startswith('5'))
errores = sum(1 for f in filas if f['success'] != 'true')
login = [int(f['elapsed']) for f in por_pedido.get(INICIO_DE_SESION, [])]

print()
print('P95 de todas las consultas: %d ms (RNF:0001, límite %d ms)' % (percentil_95(todos), LIMITE_MS))
if login:
    print('P95 del inicio de sesión:   %d ms (RNF:0005, límite %d ms)' % (percentil_95(login), LIMITE_MS))
print('Errores 5xx: %d · errores en total: %d (RNF:0004: 0 errores del servidor)' % (errores_5xx, errores))

aprobado = cumple and percentil_95(todos) < LIMITE_MS and (not login or percentil_95(login) < LIMITE_MS) and errores_5xx == 0
print()
print('Resultado: ' + ('APROBADO' if aprobado else 'NO APROBADO'))
