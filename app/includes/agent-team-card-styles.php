<?php
/**
 * Estilos compartidos — tarjeta Nuestro Equipo (foto + nombre/cargo + Instagram).
 * Incluir una sola vez por página.
 */
if (!empty($GLOBALS['am_team_card_styles_loaded'])) {
    return;
}
$GLOBALS['am_team_card_styles_loaded'] = true;
?>
<style>
.am-team-card {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: stretch;
}
.am-team-card-photo {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background: #e8edf5;
}
.am-team-card-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top center;
    display: block;
    transition: transform 0.45s ease;
}
.am-team-card:hover .am-team-card-photo img {
    transform: scale(1.04);
}
.am-team-card-photo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eef2f7;
    color: #94a3b8;
    font-family: 'Montserrat', sans-serif;
    font-weight: 800;
    font-size: 2.25rem;
}
.am-team-card-ig-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(10, 18, 40, 0.55);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.28s ease, visibility 0.28s ease;
    text-decoration: none;
    z-index: 2;
}
.am-team-card:hover .am-team-card-ig-overlay {
    opacity: 1;
    visibility: visible;
}
.am-team-card-ig-btn {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #ffffff;
    color: #111827;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s ease;
}
.am-team-card-ig-overlay:hover .am-team-card-ig-btn,
.am-team-card-ig-overlay:focus .am-team-card-ig-btn {
    transform: scale(1.06);
    color: #111827;
}
.am-team-card-info {
    position: relative;
    background: #1a2744;
    color: #ffffff;
    padding: 14px 16px 16px;
    text-align: center;
    overflow: hidden;
}
.am-team-card-info.am-team-card-info--accent {
    background: #e1001a;
}
.am-team-card-info::after {
    content: '';
    position: absolute;
    right: 0;
    bottom: 0;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 0 0 16px 16px;
    border-color: transparent transparent #ffffff transparent;
}
.am-team-card-name {
    font-family: 'Montserrat', sans-serif;
    font-weight: 800;
    font-size: 0.95rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    line-height: 1.25;
    margin: 0 0 4px;
    color: #ffffff;
}
.am-team-card-role {
    font-family: 'Poppins', sans-serif;
    font-weight: 400;
    font-size: 0.88rem;
    line-height: 1.3;
    color: rgba(255, 255, 255, 0.92);
    margin: 0;
}
</style>
