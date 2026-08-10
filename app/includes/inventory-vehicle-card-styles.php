<?php
/**
 * Estilos compartidos de tarjeta de vehículo (inventario / venta-autos).
 */
?>
<style>
.vehicle-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background: #ffffff;
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(8, 16, 38, 0.05);
}
.vehicle-card .card-body {
    background-color: #eef2f7;
    padding: 24px 20px !important;
    border-bottom-left-radius: 20px;
    border-bottom-right-radius: 20px;
    border-top: 1px solid #e2e8f0;
}
.vehicle-card .vehicle-img-container {
    display: block;
    width: 100%;
    height: 220px;
    overflow: hidden;
    padding: 0;
    margin: 0;
    line-height: 0;
    background-color: #ffffff;
    border: none;
}
.vehicle-card .vehicle-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center center;
    display: block;
    transition: transform 0.3s ease;
}
.vehicle-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(8, 16, 38, 0.12) !important;
}
.vehicle-card:hover .vehicle-img-container img {
    transform: scale(1.03);
}
.card-spec-line {
    font-size: 0.76rem;
    color: #64748b;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    border-bottom: 2px solid #cbd5e1;
    padding-bottom: 10px;
    margin-bottom: 14px;
}
.card-price-balboa {
    color: #1f347f;
    font-size: 1.55rem;
    font-weight: 800;
    font-family: 'Poppins', sans-serif;
    line-height: 1.1;
}
.card-cotizar-link {
    color: #c51f17;
    font-weight: 700;
    font-size: 0.88rem;
    font-family: 'Poppins', sans-serif;
    transition: color 0.2s ease;
}
.card-cotizar-link:hover {
    color: #081026 !important;
    text-decoration: underline !important;
}
.price-subtext-muted {
    font-size: 0.68rem;
    color: #64748b;
    font-family: 'Poppins', sans-serif;
    margin-top: 4px;
}
.inv-tipo-compra-badge {
    top: 0.75rem !important;
    left: 0.75rem !important;
}
</style>
