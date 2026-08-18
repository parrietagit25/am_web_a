<?php
/**
 * RAC booking stepper (6 steps — 5 pago, 6 confirmación).
 *
 * @var int $racStep Current step 1–6
 */
$racStep = isset($racStep) ? (int) $racStep : 1;
$racStep = max(1, min(6, $racStep));
$progress = (($racStep - 1) / 5) * 100;

$steps = [
    1 => ['label' => 'Sucursal y Fechas', 'href' => '/rent-a-car.php'],
    2 => ['label' => 'Escoger Auto', 'href' => '/resultados.php'],
    3 => ['label' => 'Escoger Extras', 'href' => '/extras.php'],
    4 => ['label' => 'Realizar Reserva', 'href' => '/reservar.php'],
    5 => ['label' => 'Pago', 'href' => '/pago.php'],
    6 => ['label' => 'Confirmación', 'href' => '#'],
];
?>
<section class="container mt-4 pt-2">
    <div class="row">
        <div class="col-12">
            <div class="stepper-container" id="racStepper">
                <div class="stepper-line"></div>
                <div class="stepper-line-active" style="width: <?php echo (float) $progress; ?>%;"></div>
                <?php foreach ($steps as $num => $step):
                    $isCompleted = $num < $racStep;
                    $isActive = $num === $racStep;
                    $clickable = $isCompleted && $num < 5;
                ?>
                <div class="step-item<?php echo $isCompleted ? ' completed' : ''; ?><?php echo $isActive ? ' active' : ''; ?>" data-step="<?php echo $num; ?>">
                    <?php if ($clickable): ?>
                    <div class="step-badge" style="cursor:pointer;" onclick="window.RAC_FLOW && window.RAC_FLOW.goToStep(<?php echo $num; ?>)">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <?php else: ?>
                    <div class="step-badge"><?php echo $isCompleted ? '<i class="bi bi-check-lg"></i>' : $num; ?></div>
                    <?php endif; ?>
                    <span class="step-title"><?php echo esc($num . '. ' . $step['label']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
