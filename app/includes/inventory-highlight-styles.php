<?php
/**
 * Estilos de etiquetas de resaltado (inventario / venta-autos / detalle).
 */
?>
<style>
/* Etiquetas de resaltado (estilo marketplace) */
.inv-highlight-tag {
    position: absolute;
    z-index: 11;
    display: inline-block;
    color: #ffffff;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    line-height: 1.15;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.18);
    font-family: 'Montserrat', sans-serif;
    max-width: 92%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.inv-highlight-tag--card {
    top: 10px;
    right: 0;
    padding: 5px 12px 5px 16px;
    border-radius: 999px 0 0 999px;
}
.inv-highlight-tag--detail {
    position: relative;
    top: auto;
    right: auto;
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 999px;
    margin-bottom: 12px;
    font-size: 0.72rem;
    max-width: 100%;
    white-space: normal;
}
.inv-highlight--nuevo {
    background: linear-gradient(135deg, #059669 0%, #10b981 55%, #34d399 100%);
}
.inv-highlight--ultimas {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 55%, #f97316 100%);
}
.inv-highlight--pocas {
    background: linear-gradient(135deg, #c2410c 0%, #ea580c 55%, #fb923c 100%);
}
.inv-highlight--oferta {
    background: linear-gradient(135deg, #be123c 0%, #e11d48 55%, #f43f5e 100%);
}
.inv-highlight--destacado {
    background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 55%, #a78bfa 100%);
}
.inv-highlight--promo { background: #9f1239; }
.inv-highlight--featured { background: #4c1d95; }
.inv-highlight--recommended { background: #166534; }
.inv-highlight--popular { background: #713f12; }
.inv-highlight--custom { background: #1f2937; }
.inv-highlight-preview {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 800;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
</style>
