<?php
/**
 * Estilos compartidos — tarjeta Nuestro Equipo (foto + nombre/cargo + contacto).
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
.am-team-card-contact-overlay,
.am-team-card-ig-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px;
    background: rgba(10, 18, 40, 0.58);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.28s ease, visibility 0.28s ease;
    text-decoration: none;
    z-index: 2;
}
.am-team-card:hover .am-team-card-contact-overlay,
.am-team-card:hover .am-team-card-ig-overlay,
.am-team-card:focus-within .am-team-card-contact-overlay {
    opacity: 1;
    visibility: visible;
}
.am-team-card-email-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    max-width: 94%;
    background: #ffffff;
    color: #111827;
    border-radius: 999px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
    padding: 10px 14px;
    transition: transform 0.2s ease;
    text-decoration: none;
}
.am-team-card-contact-overlay:hover .am-team-card-email-chip,
.am-team-card-email-chip:focus {
    transform: scale(1.03);
}
.am-team-card-email-chip .bi-envelope-fill {
    flex-shrink: 0;
    font-size: 1rem;
    color: #c51f17;
}
.am-team-card-email-text {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    line-height: 1.2;
    word-break: break-word;
    overflow-wrap: anywhere;
    text-align: left;
}
.am-team-card-email-chip--short .am-team-card-email-text { font-size: 0.82rem; }
.am-team-card-email-chip--medium .am-team-card-email-text { font-size: 0.7rem; }
.am-team-card-email-chip--long {
    border-radius: 16px;
    padding: 10px 12px;
}
.am-team-card-email-chip--long .am-team-card-email-text { font-size: 0.6rem; }
.am-team-card-ig-mini {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.95);
    color: #111827;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    text-decoration: none;
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
    text-decoration: none;
}
.am-team-card-ig-btn:hover,
.am-team-card-ig-mini:hover {
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
