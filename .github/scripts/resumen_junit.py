#!/usr/bin/env python3
"""Resumen de los resultados de PHPUnit para GitHub Actions.

Lee el reporte JUnit (--log-junit) y, si existe, la cobertura en texto
(--coverage-text). Escribe una tabla en el resumen del trabajo
($GITHUB_STEP_SUMMARY) y deja anotaciones: una con los totales y una por
cada prueba fallida, para verlas en la pestaña Checks del pull request.

Uso: python3 resumen_junit.py reportes/junit.xml [reportes/cobertura.txt]
"""
import os
import re
import sys
import xml.etree.ElementTree as ET


def escapar(texto):
    # Formato de los comandos de flujo de trabajo de GitHub Actions
    return texto.replace('%', '%25').replace('\r', '').replace('\n', '%0A')


def escapar_propiedad(texto):
    # En las propiedades (title=...) también se escapan ':' y ','
    return escapar(texto).replace(':', '%3A').replace(',', '%2C')


def main():
    junit = sys.argv[1]
    cobertura = sys.argv[2] if len(sys.argv) > 2 else None
    if not os.path.exists(junit):
        print('::error title=PHPUnit::No se generó el reporte JUnit')
        return 1

    raiz = ET.parse(junit).getroot()
    casos = raiz.findall('.//testcase')
    fallas, saltadas = [], 0
    for caso in casos:
        problema = caso.find('failure') if caso.find('failure') is not None else caso.find('error')
        if problema is not None:
            nombre = caso.get('class', '').split('\\')[-1] + '::' + caso.get('name', '')
            mensaje = (problema.text or problema.get('message') or '').strip()
            fallas.append((nombre, mensaje))
        elif caso.find('skipped') is not None:
            saltadas += 1

    total = len(casos)
    aprobadas = total - len(fallas) - saltadas
    lineas_cobertura = []
    if cobertura and os.path.exists(cobertura):
        with open(cobertura, encoding='utf-8', errors='replace') as f:
            limpio = re.sub(r'\x1b\[[0-9;]*m', '', f.read())
        lineas_cobertura = [l.strip() for l in limpio.splitlines() if re.match(r'\s*(Classes|Methods|Lines):', l)]

    resumen = [
        '## Resultados de PHPUnit',
        '',
        '| Total | Aprobadas | Fallidas | Omitidas |',
        '| --- | --- | --- | --- |',
        f'| {total} | {aprobadas} | {len(fallas)} | {saltadas} |',
    ]
    if lineas_cobertura:
        resumen += ['', '**Cobertura:** ' + ' · '.join(lineas_cobertura)]
    if fallas:
        resumen += ['', '### Pruebas fallidas', '']
        for nombre, mensaje in fallas:
            primera = mensaje.splitlines()[0] if mensaje else ''
            resumen.append(f'- `{nombre}`: {primera}')

    destino = os.environ.get('GITHUB_STEP_SUMMARY')
    if destino:
        with open(destino, 'a', encoding='utf-8') as f:
            f.write('\n'.join(resumen) + '\n')

    texto = f'Total {total} · aprobadas {aprobadas} · fallidas {len(fallas)} · omitidas {saltadas}'
    if lineas_cobertura:
        texto += '\n' + '\n'.join(lineas_cobertura)
    if fallas:
        texto += '\nFallidas:\n' + '\n'.join(n for n, _ in fallas)
    print(f'::notice title=Resultados PHPUnit::{escapar(texto)}')
    for nombre, mensaje in fallas[:9]:
        print(f'::error title={escapar_propiedad(nombre)}::{escapar(mensaje[:3000])}')
    return 0


if __name__ == '__main__':
    sys.exit(main())
