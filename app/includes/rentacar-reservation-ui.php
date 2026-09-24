<?php
/**
 * Textos UI del flujo de reservas RAC (ES/EN).
 * No incluye lógica de disponibilidad, tarifas ni checkout.
 */

/**
 * @return array<string, array{title: string, help: string, fields: array<string, array<string, string>>}>
 */
function rac_reservation_ui_schema(): array
{
    return [
        'buttons' => [
            'title' => 'Edición de botones',
            'help' => 'Textos de los botones de la tarjeta de vehículo en /resultados.php. Solo cambia la etiqueta; el color y la acción de reserva no se modifican.',
            'fields' => [
                'reserve_web' => ['label' => 'Botón rojo (Reservar Web)', 'es' => 'Reservar Web', 'en' => 'Book online'],
                'reserve' => ['label' => 'Botón blanco (Reservar)', 'es' => 'Reservar', 'en' => 'Reserve'],
            ],
        ],
        'search' => [
            'title' => 'Buscador',
            'help' => 'Formulario de /rent-a-car.php. Sucursales, fechas y códigos no se editan aquí.',
            'fields' => [
                'title' => ['label' => 'Título', 'es' => 'Reserva tu Vehículo', 'en' => 'Book Your Vehicle'],
                'pickup_branch' => ['label' => 'Sucursal de retiro', 'es' => 'Sucursal de Retiro', 'en' => 'Pickup location'],
                'return_branch' => ['label' => 'Sucursal de devolución', 'es' => 'Sucursal de Devolución', 'en' => 'Return location'],
                'pickup_date' => ['label' => 'Fecha de retiro', 'es' => 'Fecha de Retiro', 'en' => 'Pickup date'],
                'return_date' => ['label' => 'Fecha de devolución', 'es' => 'Fecha de Devolución', 'en' => 'Return date'],
                'driver_age' => ['label' => 'Edad del conductor', 'es' => 'Edad del conductor', 'en' => "Driver's age"],
                'age_placeholder' => ['label' => 'Placeholder edad', 'es' => 'Selecciona la edad...', 'en' => 'Select age...'],
                'age_25' => ['label' => 'Opción 25+', 'es' => '25 años o más', 'en' => '25 years or older'],
                'age_23' => ['label' => 'Opción 23-24', 'es' => '23-24 años', 'en' => '23–24 years'],
                'age_required' => ['label' => 'Validación edad', 'es' => 'Por favor selecciona la edad del conductor.', 'en' => "Please select the driver's age."],
                'age_hint' => ['label' => 'Leyenda menores de 23', 'es' => 'Menores de 23 años: contacte la sucursal por teléfono.', 'en' => 'Drivers under 23: please contact the branch by phone.', 'type' => 'textarea'],
                'branch_placeholder' => ['label' => 'Placeholder sucursal', 'es' => 'Selecciona sucursal...', 'en' => 'Select a branch...'],
                'pickup_required' => ['label' => 'Validación sucursal', 'es' => 'Por favor selecciona la sucursal de retiro.', 'en' => 'Please select a pickup branch.'],
                'coupon_label' => ['label' => 'Código de cupón', 'es' => 'Código de Cupón', 'en' => 'Coupon code'],
                'coupon_placeholder' => ['label' => 'Placeholder cupón', 'es' => 'Ej. DESCUENTO10', 'en' => 'e.g. DISCOUNT10'],
                'toggle_return' => ['label' => 'Switch otra sucursal', 'es' => 'Devolver en otra sucursal', 'en' => 'Return to another branch'],
                'toggle_coupon' => ['label' => 'Switch cupón', 'es' => 'Tengo un cupón', 'en' => 'I have a coupon'],
                'submit' => ['label' => 'Botón buscar', 'es' => 'BUSCAR VEHÍCULO', 'en' => 'SEARCH VEHICLE'],
                'alert_return_adjusted' => ['label' => 'Alerta devolución ajustada', 'es' => '{branch} no acepta devoluciones ese día. Ajustamos al {date}.', 'en' => '{branch} does not accept returns that day. We adjusted it to {date}.', 'type' => 'textarea', 'hint' => 'Use {branch} y {date}.'],
                'alert_return_closed' => ['label' => 'Alerta devolución cerrada', 'es' => '{branch} no opera devoluciones en las fechas seleccionadas. Elija otra fecha.', 'en' => '{branch} does not operate returns on the selected dates. Please choose another date.', 'type' => 'textarea', 'hint' => 'Use {branch}.'],
                'alert_pickup_closed' => ['label' => 'Alerta retiro cerrado', 'es' => 'La sucursal de retiro no opera ese día. Elija otra fecha.', 'en' => 'The pickup branch is closed that day. Please choose another date.', 'type' => 'textarea'],
                'loader_title' => ['label' => 'Loader título', 'es' => 'Consultando Disponibilidad', 'en' => 'Checking availability'],
                'loader_subtitle' => ['label' => 'Loader subtítulo', 'es' => 'Buscando vehículos en tiempo real…', 'en' => 'Searching for vehicles in real time…'],
                'api_error' => ['label' => 'Error API', 'es' => 'No se pudo consultar la disponibilidad.', 'en' => 'We could not check availability.'],
                'connection_error' => ['label' => 'Error de conexión', 'es' => 'Error de conexión. Intente nuevamente.', 'en' => 'Connection error. Please try again.'],
            ],
        ],
        'stepper' => [
            'title' => 'Pasos del flujo',
            'help' => 'Barra superior en resultados, extras, reservar, pago y confirmación.',
            'fields' => [
                'step1' => ['label' => 'Paso 1', 'es' => 'Sucursal y Fechas', 'en' => 'Branch and dates'],
                'step2' => ['label' => 'Paso 2', 'es' => 'Escoger Auto', 'en' => 'Choose a car'],
                'step3' => ['label' => 'Paso 3', 'es' => 'Escoger Extras', 'en' => 'Choose extras'],
                'step4' => ['label' => 'Paso 4', 'es' => 'Realizar Reserva', 'en' => 'Make reservation'],
                'step5' => ['label' => 'Paso 5', 'es' => 'Pago', 'en' => 'Payment'],
                'step6' => ['label' => 'Paso 6', 'es' => 'Confirmación', 'en' => 'Confirmation'],
            ],
        ],
        'results' => [
            'title' => 'Resultados',
            'help' => 'Página /resultados.php. Nombres de vehículos y sucursales vienen de la API.',
            'fields' => [
                'live_badge' => ['label' => 'Badge', 'es' => 'Disponibilidad en Vivo', 'en' => 'Live availability'],
                'heading' => ['label' => 'Título', 'es' => 'Escoge tu categoría', 'en' => 'Choose your category'],
                'summary_loading' => ['label' => 'Cargando criterios', 'es' => 'Cargando criterios de búsqueda...', 'en' => 'Loading search details...'],
                'modify' => ['label' => 'Modificar búsqueda', 'es' => 'Modificar Búsqueda', 'en' => 'Modify search'],
                'filter_all' => ['label' => 'Filtro todos', 'es' => 'Todos', 'en' => 'All'],
                'processing' => ['label' => 'Procesando', 'es' => 'Procesando...', 'en' => 'Processing...'],
                'loading' => ['label' => 'Cargando', 'es' => 'Cargando disponibilidad...', 'en' => 'Loading availability...'],
                'empty_title' => ['label' => 'Sin búsqueda título', 'es' => 'No has realizado ninguna búsqueda', 'en' => 'You have not started a search'],
                'empty_text' => ['label' => 'Sin búsqueda texto', 'es' => 'Para ver vehículos disponibles, primero dinos cuándo y dónde necesitas retirar tu auto.', 'en' => 'To see available vehicles, first tell us when and where you need to pick up your car.', 'type' => 'textarea'],
                'empty_cta' => ['label' => 'Ir al buscador', 'es' => 'Ir al Buscador', 'en' => 'Go to search'],
                'sidebar_title' => ['label' => 'Sidebar título', 'es' => 'Tu reserva', 'en' => 'Your reservation'],
                'sidebar_empty' => ['label' => 'Sidebar vacío', 'es' => 'Complete la búsqueda para ver el resumen.', 'en' => 'Complete the search to see the summary.'],
                'web_exclusive' => ['label' => 'Tarifa web', 'es' => 'WebExclusivo', 'en' => 'WebExclusive'],
                'per_day' => ['label' => 'Por día', 'es' => '/día', 'en' => '/day'],
                'days' => ['label' => 'Días (plural)', 'es' => 'días', 'en' => 'days'],
                'day' => ['label' => 'Día (singular)', 'es' => 'día', 'en' => 'day'],
                'approx_price' => ['label' => 'Precio aproximado', 'es' => 'Precio aproximado', 'en' => 'Approximate price'],
                'pax' => ['label' => 'Pasajeros', 'es' => 'Pax', 'en' => 'Pax'],
                'automatic' => ['label' => 'Transmisión fallback', 'es' => 'Automática', 'en' => 'Automatic'],
                'empty_closed' => ['label' => 'Sucursal cerrada', 'es' => 'Esta sucursal no acepta devoluciones en esa fecha. Cambie la fecha de devolución.', 'en' => 'This branch does not accept returns on that date. Please change the return date.', 'type' => 'textarea'],
                'empty_none' => ['label' => 'Sin disponibilidad', 'es' => 'No hay vehículos disponibles. Pruebe otras fechas o sucursales.', 'en' => 'No vehicles available. Try other dates or branches.', 'type' => 'textarea'],
                'empty_rate' => ['label' => 'Tarifa no configurada', 'es' => 'Tarifa no disponible para esa sucursal. Contacte a la sucursal.', 'en' => 'Rate not available for that branch. Please contact the branch.', 'type' => 'textarea'],
                'empty_timeout' => ['label' => 'Sistema lento', 'es' => 'El sistema está lento. Reintente en unos minutos.', 'en' => 'The system is slow. Please try again in a few minutes.', 'type' => 'textarea'],
                'empty_generic' => ['label' => 'Sin resultados genérico', 'es' => 'Sin resultados para esta búsqueda. Ajuste fechas o sucursales.', 'en' => 'No results for this search. Adjust dates or branches.', 'type' => 'textarea'],
            ],
        ],
        'extras' => [
            'title' => 'Extras',
            'help' => 'Página /extras.php. Nombres de protecciones y extras vienen del catálogo/API.',
            'fields' => [
                'redirect' => ['label' => 'Redirigiendo', 'es' => 'Redirigiendo al buscador…', 'en' => 'Redirecting to search…'],
                'back' => ['label' => 'Volver', 'es' => 'Volver a Escoger Auto', 'en' => 'Back to choose a car'],
                'updating' => ['label' => 'Actualizando precios', 'es' => 'Actualizando precios…', 'en' => 'Updating prices…'],
                'protection_heading' => ['label' => 'Título protecciones', 'es' => 'Nivel de protección', 'en' => 'Protection level'],
                'extras_heading' => ['label' => 'Título extras', 'es' => '¿Quieres agregar algún extra?', 'en' => 'Want to add an extra?'],
                'also_heading' => ['label' => 'También te interesa', 'es' => 'También te puede interesar', 'en' => 'You may also like'],
                'also_sub' => ['label' => 'Subtítulo alternativas', 'es' => 'Mismas fechas · cambio sin complicaciones', 'en' => 'Same dates · easy to change'],
                'charges_heading' => ['label' => 'Resumen de cargos', 'es' => 'Resumen de cargos', 'en' => 'Charges summary'],
                'base_rate' => ['label' => 'Tarifa base', 'es' => 'Tarifa base', 'en' => 'Base rate'],
                'saf' => ['label' => 'SAF', 'es' => 'SAF', 'en' => 'SAF'],
                'protection' => ['label' => 'Protección', 'es' => 'Protección', 'en' => 'Protection'],
                'extra_driver' => ['label' => 'Conductor adicional', 'es' => 'Conductor adicional', 'en' => 'Additional driver'],
                'other_extras' => ['label' => 'Otros extras', 'es' => 'Otros extras', 'en' => 'Other extras'],
                'itbms' => ['label' => 'ITBMS', 'es' => 'ITBMS (7%)', 'en' => 'ITBMS (7%)'],
                'total' => ['label' => 'Total', 'es' => 'Total', 'en' => 'Total'],
                'continue' => ['label' => 'Continuar', 'es' => 'Continuar', 'en' => 'Continue'],
                'age_surcharge' => ['label' => 'Cargo por edad', 'es' => 'Cargo por edad (23-24 años)', 'en' => 'Age surcharge (ages 23–24)'],
                'other_branch' => ['label' => 'Devolución otra sucursal', 'es' => 'Devolución en otra sucursal', 'en' => 'Return at another branch'],
                'saf_admin' => ['label' => 'Cargo administrativo', 'es' => 'Cargo Administrativo (SAF)', 'en' => 'Administrative fee (SAF)'],
            ],
        ],
        'checkout' => [
            'title' => 'Reservar (datos del conductor)',
            'help' => 'Página /reservar.php. No cambia validaciones de pago ni captcha.',
            'fields' => [
                'incomplete_title' => ['label' => 'Sesión incompleta', 'es' => 'Sesión de reserva incompleta', 'en' => 'Incomplete reservation session'],
                'incomplete_text' => ['label' => 'Sesión incompleta texto', 'es' => 'Seleccione vehículo y extras para continuar.', 'en' => 'Select a vehicle and extras to continue.'],
                'go_search' => ['label' => 'Ir al buscador', 'es' => 'Ir al buscador', 'en' => 'Go to search'],
                'back' => ['label' => 'Volver extras', 'es' => 'Volver a Escoger Extras', 'en' => 'Back to extras'],
                'driver_heading' => ['label' => 'Datos del conductor', 'es' => 'Datos del conductor principal', 'en' => 'Primary driver details'],
                'first_name' => ['label' => 'Nombre', 'es' => 'Nombre', 'en' => 'First name'],
                'last_name' => ['label' => 'Apellido', 'es' => 'Apellido', 'en' => 'Last name'],
                'email' => ['label' => 'Correo', 'es' => 'Correo electrónico', 'en' => 'Email'],
                'email_confirm' => ['label' => 'Confirmar correo', 'es' => 'Confirmar correo', 'en' => 'Confirm email'],
                'phone_country' => ['label' => 'País tel.', 'es' => 'País tel.', 'en' => 'Phone country'],
                'phone_country_search' => ['label' => 'Buscar país tel.', 'es' => 'Buscar país o código...', 'en' => 'Search country or code...'],
                'err_phone_country' => ['label' => 'Validación país tel.', 'es' => 'Debe escoger un código de área', 'en' => 'Please choose an area code'],
                'phone' => ['label' => 'Teléfono', 'es' => 'Teléfono', 'en' => 'Phone'],
                'doc_heading' => ['label' => 'Documento', 'es' => 'Documento de identidad', 'en' => 'ID document'],
                'doc_type' => ['label' => 'Tipo', 'es' => 'Tipo', 'en' => 'Type'],
                'doc_license' => ['label' => 'Licencia', 'es' => 'Licencia de conducir', 'en' => "Driver's license"],
                'doc_passport' => ['label' => 'Pasaporte', 'es' => 'Pasaporte', 'en' => 'Passport'],
                'doc_id' => ['label' => 'Cédula', 'es' => 'Cédula', 'en' => 'National ID'],
                'doc_number' => ['label' => 'Número', 'es' => 'Número', 'en' => 'Number'],
                'issuer_country' => ['label' => 'País emisor', 'es' => 'País emisor', 'en' => 'Issuing country'],
                'country_pa' => ['label' => 'Panamá', 'es' => 'Panamá', 'en' => 'Panama'],
                'country_us' => ['label' => 'Estados Unidos', 'es' => 'Estados Unidos', 'en' => 'United States'],
                'country_co' => ['label' => 'Colombia', 'es' => 'Colombia', 'en' => 'Colombia'],
                'country_cr' => ['label' => 'Costa Rica', 'es' => 'Costa Rica', 'en' => 'Costa Rica'],
                'birth_date' => ['label' => 'Fecha de nacimiento', 'es' => 'Fecha de nacimiento', 'en' => 'Date of birth'],
                'flight_heading' => ['label' => 'Info vuelo', 'es' => 'Información de vuelo', 'en' => 'Flight information'],
                'optional' => ['label' => 'Opcional', 'es' => '(opcional)', 'en' => '(optional)'],
                'flight_help' => ['label' => 'Ayuda vuelo', 'es' => 'Si llega al aeropuerto, esto nos ayuda a coordinar la entrega del vehículo.', 'en' => 'If you arrive at the airport, this helps us coordinate vehicle delivery.', 'type' => 'textarea'],
                'flight_number' => ['label' => 'Número de vuelo', 'es' => 'Número de vuelo', 'en' => 'Flight number'],
                'airline_code' => ['label' => 'Código aerolínea', 'es' => 'Código de aerolínea', 'en' => 'Airline code'],
                'notes' => ['label' => 'Notas', 'es' => 'Notas adicionales', 'en' => 'Additional notes'],
                'notes_placeholder' => ['label' => 'Placeholder notas', 'es' => 'Horario de llegada, peticiones especiales…', 'en' => 'Arrival time, special requests…'],
                'terms_label' => ['label' => 'Términos (antes del enlace)', 'es' => 'He leído y acepto los', 'en' => 'I have read and accept the'],
                'terms_link' => ['label' => 'Texto enlace términos', 'es' => 'Términos y Condiciones', 'en' => 'Terms and Conditions'],
                'terms_after' => ['label' => 'Términos (después del enlace)', 'es' => 'de alquiler, incluyendo política de cancelación y depósito de garantía.', 'en' => 'of rental, including the cancellation policy and security deposit.'],
                'payment_heading' => ['label' => 'Cómo completar', 'es' => '¿Cómo desea completar su reserva?', 'en' => 'How would you like to complete your reservation?'],
                'payment_intro' => ['label' => 'Intro pago', 'es' => 'En ambos casos su vehículo queda reservado en el sistema. Elija si paga ahora en línea o al retirar en sucursal.', 'en' => 'In both cases your vehicle is reserved. Choose whether to pay online now or at the branch.', 'type' => 'textarea'],
                'pay_online' => ['label' => 'Pagar en línea', 'es' => 'Pagar ahora en línea', 'en' => 'Pay online now'],
                'pay_online_help' => ['label' => 'Ayuda pago en línea', 'es' => 'Cobro seguro con tarjeta (3-D Secure). La reserva se confirma al aprobar el pago.', 'en' => 'Secure card payment (3-D Secure). The reservation is confirmed when payment is approved.', 'type' => 'textarea'],
                'pay_online_next_hint' => [
                    'label' => 'Aviso pago en línea (turquesa)',
                    'es' => 'El siguiente paso es el pago seguro con tarjeta. La reserva se confirma al aprobar el cobro. El dueño de la tarjeta tiene que estar presente al momento de retirar el auto.',
                    'en' => 'The next step is secure card payment. The reservation is confirmed when the charge is approved. The cardholder must be present when picking up the vehicle.',
                    'type' => 'textarea',
                    'hint' => 'Se muestra al elegir pagar con tarjeta. La última frase se resalta en negrita.',
                ],
                'pay_online_price' => ['label' => 'Precio si paga con tarjeta', 'es' => 'Precio con tarjeta: {price}', 'en' => 'Card price: {price}', 'hint' => 'Use {price}. Solo se muestra en reservas de mostrador.'],
                'pay_online_save' => ['label' => 'Ahorro si paga con tarjeta', 'es' => 'Ahorras {amount} si pagas con tarjeta', 'en' => 'You save {amount} by paying with card', 'hint' => 'Use {amount}.'],
                'pay_counter' => ['label' => 'Pagar en sucursal', 'es' => 'Reservar y pagar en sucursal', 'en' => 'Reserve and pay at the branch'],
                'pay_counter_help' => ['label' => 'Ayuda pago sucursal', 'es' => 'Confirmamos su vehículo ahora. El total se paga al recogerlo. Puede aplicarse depósito en garantía con tarjeta.', 'en' => 'We confirm your vehicle now. The total is paid at pickup. A card security deposit may apply.', 'type' => 'textarea'],
                'submit_pay' => ['label' => 'Botón ir a pago', 'es' => 'Continuar al pago seguro', 'en' => 'Continue to secure payment'],
                'submit_reserve' => ['label' => 'Botón confirmar', 'es' => 'Confirmar y reservar', 'en' => 'Confirm and reserve'],
                'err_first_name' => ['label' => 'Error nombre', 'es' => 'Ingrese su nombre.', 'en' => 'Enter your first name.'],
                'err_last_name' => ['label' => 'Error apellido', 'es' => 'Ingrese su apellido.', 'en' => 'Enter your last name.'],
                'err_email' => ['label' => 'Error correo', 'es' => 'Correo inválido.', 'en' => 'Invalid email.'],
                'err_email_match' => ['label' => 'Error correos', 'es' => 'Los correos deben coincidir.', 'en' => 'Emails must match.'],
                'err_phone' => ['label' => 'Error teléfono', 'es' => 'Ingrese su teléfono.', 'en' => 'Enter your phone number.'],
                'err_doc' => ['label' => 'Error documento', 'es' => 'Ingrese el número de documento.', 'en' => 'Enter the document number.'],
                'err_birth' => ['label' => 'Error nacimiento', 'es' => 'Ingrese una fecha de nacimiento válida.', 'en' => 'Enter a valid date of birth.'],
                'err_terms' => ['label' => 'Error términos', 'es' => 'Debe aceptar los términos.', 'en' => 'You must accept the terms.'],
            ],
        ],
        'payment' => [
            'title' => 'Pago',
            'help' => 'Página /pago.php. No cambia PowerTranz ni montos.',
            'fields' => [
                'empty_title' => ['label' => 'Sin pago', 'es' => 'No hay un pago pendiente', 'en' => 'There is no pending payment'],
                'empty_text' => ['label' => 'Sin pago texto', 'es' => 'Complete los datos del conductor para continuar al cobro.', 'en' => 'Complete the driver details to continue to payment.'],
                'empty_cta' => ['label' => 'Ir a datos', 'es' => 'Ir a datos de reserva', 'en' => 'Go to reservation details'],
                'back' => ['label' => 'Volver', 'es' => 'Volver a datos del conductor', 'en' => 'Back to driver details'],
                'heading' => ['label' => 'Título', 'es' => 'Pago seguro', 'en' => 'Secure payment'],
                'intro' => ['label' => 'Intro', 'es' => 'El cargo se procesa en PowerTranz (3-D Secure). Automarket no almacena el número de tarjeta.', 'en' => 'The charge is processed in PowerTranz (3-D Secure). Automarket does not store the card number.', 'type' => 'textarea'],
                'preparing' => ['label' => 'Preparando', 'es' => 'Preparando formulario de pago…', 'en' => 'Preparing the payment form…'],
                'total' => ['label' => 'Total a pagar', 'es' => 'Total a pagar', 'en' => 'Amount due'],
                'cancel_note' => [
                    'label' => 'Restricciones de pago / tarjeta',
                    'es' => "Restricciones importantes\n• No aceptamos tarjetas virtuales ni tarjetas sin estampado en relieve.\n• Debes presentar la misma tarjeta de crédito con la que pagaste tu reservación al momento de retirar el vehículo.\n• El dueño de la tarjeta de crédito con que se pagó la reservación debe estar presente al momento de retirar el auto.",
                    'en' => "Important restrictions\n• We do not accept virtual cards or cards without embossed printing.\n• You must present the same credit card used to pay for your reservation when picking up the vehicle.\n• The credit card holder who paid for the reservation must be present at vehicle pickup.",
                    'type' => 'textarea',
                ],
            ],
        ],
        'confirm' => [
            'title' => 'Confirmación',
            'help' => 'Página /confirmacion.php.',
            'fields' => [
                'heading' => ['label' => 'Título', 'es' => '¡Reserva Confirmada!', 'en' => 'Reservation confirmed!'],
                'email_note' => ['label' => 'Nota correo', 'es' => 'Te enviamos los detalles a tu correo electrónico.', 'en' => 'We sent the details to your email.'],
                'code_label' => ['label' => 'Número de confirmación', 'es' => 'Número de confirmación', 'en' => 'Confirmation number'],
                'counter_note' => ['label' => 'Nota pago sucursal', 'es' => 'Pago pendiente en sucursal. No se realizó cobro en línea. Al recoger el vehículo, presenta tu número de confirmación, una licencia válida y la tarjeta de crédito a nombre del conductor principal (depósito / pago en mostrador).', 'en' => 'Payment pending at the branch. No online charge was made. When picking up the vehicle, present your confirmation number, a valid license, and the credit card in the primary driver’s name (deposit / counter payment).', 'type' => 'textarea'],
                'online_note' => ['label' => 'Nota pago en línea', 'es' => 'Al recoger el vehículo, presenta tu número de confirmación, una licencia válida y la tarjeta de crédito a nombre del conductor principal.', 'en' => 'When picking up the vehicle, present your confirmation number, a valid license, and the credit card in the primary driver’s name.', 'type' => 'textarea'],
                'new_reservation' => ['label' => 'Nueva reserva', 'es' => 'Nueva reserva', 'en' => 'New reservation'],
                'print' => ['label' => 'Imprimir', 'es' => 'Imprimir comprobante', 'en' => 'Print receipt'],
                'my_reservation' => ['label' => 'Mi reserva', 'es' => 'Mi reserva', 'en' => 'My reservation'],
            ],
        ],
        'alerts' => [
            'title' => 'Mensajes Alertas',
            'help' => 'Avisos del flujo de reserva. Use {code} para el código de cupón y {branch} / {date} donde aplique.',
            'fields' => [
                'promo_not_applicable' => [
                    'label' => 'Cupón no aplica',
                    'es' => 'Este código no aplica a esta búsqueda (clase de vehículo o sucursal de retiro).',
                    'en' => 'This code does not apply to this search (vehicle class or pickup location).',
                    'type' => 'textarea',
                    'hint' => 'Se muestra en resultados si el código no cubre la sucursal de retiro o la clase.',
                ],
                'promo_applied' => [
                    'label' => 'Cupón aplicado',
                    'es' => 'Código {code} aplicado al tiempo y millaje.',
                    'en' => 'Code {code} applied to time and mileage.',
                    'type' => 'textarea',
                    'hint' => 'Use {code}.',
                ],
                'promo_not_discounted' => [
                    'label' => 'Cupón no descontó',
                    'es' => 'El código {code} no descontó en esta búsqueda. Si es válido, se aplicará al confirmar la reserva.',
                    'en' => 'Code {code} did not discount this search. If it is valid, it will apply when the reservation is confirmed.',
                    'type' => 'textarea',
                    'hint' => 'Use {code}. Se muestra si el código no está en Promo-Code o RentWorks no descontó.',
                ],
                'live_approx' => [
                    'label' => 'Precios aproximados',
                    'es' => 'Cargando precios en vivo… Los precios mostrados son aproximados. Reintente en unos segundos.',
                    'en' => 'Loading live prices… The prices shown are approximate. Please try again in a few seconds.',
                    'type' => 'textarea',
                ],
            ],
        ],
    ];
}

/**
 * Campos de alerta del flujo RAC (para Generales → Mensajes Alertas).
 *
 * @return list<array{group:string,key:string,label:string,es:string,en:string,type:string,hint:string}>
 */
function rac_alert_message_entries(): array
{
    $out = [];
    $seen = [];
    foreach (rac_reservation_ui_schema() as $group => $meta) {
        foreach ($meta['fields'] as $key => $field) {
            $isAlert = $group === 'alerts'
                || str_starts_with($key, 'alert_')
                || str_starts_with($key, 'err_')
                || str_starts_with($key, 'empty_')
                || in_array($key, ['api_error', 'connection_error', 'age_required', 'pickup_required', 'incomplete_text', 'incomplete_title', 'pay_online_next_hint'], true);
            if (!$isAlert) {
                continue;
            }
            $id = $group . '.' . $key;
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $out[] = [
                'group' => $group,
                'key' => $key,
                'label' => (string) ($field['label'] ?? $key),
                'es' => (string) ($field['es'] ?? ''),
                'en' => (string) ($field['en'] ?? ''),
                'type' => (string) ($field['type'] ?? 'text'),
                'hint' => (string) ($field['hint'] ?? ''),
            ];
        }
    }

    return $out;
}

/**
 * Rellena ES/EN vacíos con los defaults del schema. No pisa textos ya guardados.
 *
 * @param array<string, mixed> $existing
 * @return array<string, mixed>
 */
function rac_reservation_ui_seed_from_schema(array $existing = []): array
{
    $out = $existing;
    $existingButtons = is_array($existing['buttons'] ?? null) ? $existing['buttons'] : [];
    foreach (rac_reservation_ui_schema() as $group => $meta) {
        $groupSaved = is_array($out[$group] ?? null) ? $out[$group] : [];
        foreach ($meta['fields'] as $key => $field) {
            if (trim((string) ($groupSaved[$key] ?? '')) === '') {
                $groupSaved[$key] = (string) ($field['es'] ?? '');
            }
            if (trim((string) ($groupSaved[$key . '_en'] ?? '')) === '') {
                $groupSaved[$key . '_en'] = (string) ($field['en'] ?? '');
            }
        }
        $out[$group] = $groupSaved;
    }

    $resultsSaved = is_array($out['results'] ?? null) ? $out['results'] : [];
    $buttonsSaved = is_array($out['buttons'] ?? null) ? $out['buttons'] : [];
    foreach (['reserve_web', 'reserve'] as $key) {
        if (trim((string) ($existingButtons[$key] ?? '')) === '' && trim((string) ($resultsSaved[$key] ?? '')) !== '') {
            $buttonsSaved[$key] = (string) $resultsSaved[$key];
        }
        if (trim((string) ($existingButtons[$key . '_en'] ?? '')) === '' && trim((string) ($resultsSaved[$key . '_en'] ?? '')) !== '') {
            $buttonsSaved[$key . '_en'] = (string) $resultsSaved[$key . '_en'];
        }
    }
    $out['buttons'] = $buttonsSaved;

    return $out;
}

/**
 * @param array<string, mixed> $savedGroup
 */
function rac_reservation_ui_field_text(array $savedGroup, string $key, string $defaultEs, string $defaultEn): string
{
    $lang = function_exists('current_lang') ? current_lang() : 'es';
    if ($lang === 'en') {
        $en = trim((string) ($savedGroup[$key . '_en'] ?? ''));
        if ($en !== '') {
            return $en;
        }

        return $defaultEn !== '' ? $defaultEn : $defaultEs;
    }
    $es = trim((string) ($savedGroup[$key] ?? ''));

    return $es !== '' ? $es : $defaultEs;
}

/**
 * @param array<string, mixed> $homepage
 * @return array<string, string>
 */
function rac_reservation_ui_copy(array $homepage): array
{
    $saved = is_array($homepage['reservation_ui'] ?? null) ? $homepage['reservation_ui'] : [];
    $out = [];
    foreach (rac_reservation_ui_schema() as $group => $meta) {
        $groupSaved = is_array($saved[$group] ?? null) ? $saved[$group] : [];
        foreach ($meta['fields'] as $key => $field) {
            $text = rac_reservation_ui_field_text(
                $groupSaved,
                $key,
                (string) ($field['es'] ?? ''),
                (string) ($field['en'] ?? '')
            );
            // Sustituir copy legado del paso de pago por las restricciones vigentes.
            if ($group === 'payment' && $key === 'cancel_note') {
                $legacy = [
                    'Si cancela o el banco rechaza el cobro, la reserva no se confirma.',
                    'If you cancel or the bank declines the charge, the reservation is not confirmed.',
                    'La reserva se confirma solo si el pago es aprobado. Si cancela el formulario o su banco rechaza la tarjeta, no se cobrará nada y deberá iniciar la reserva de nuevo.',
                    'Your reservation is confirmed only after payment is approved. If you cancel or your bank declines the card, you will not be charged and will need to start the reservation again.',
                ];
                if (in_array($text, $legacy, true)) {
                    $lang = function_exists('current_lang') ? current_lang() : 'es';
                    $text = $lang === 'en'
                        ? (string) ($field['en'] ?? $field['es'] ?? '')
                        : (string) ($field['es'] ?? '');
                }
            }
            $out[$group . '_' . $key] = $text;
        }
    }

    $buttonsSaved = is_array($saved['buttons'] ?? null) ? $saved['buttons'] : [];
    $resultsSaved = is_array($saved['results'] ?? null) ? $saved['results'] : [];
    $buttonDefaults = [
        'reserve_web' => ['es' => 'Reservar Web', 'en' => 'Book online'],
        'reserve' => ['es' => 'Reservar', 'en' => 'Reserve'],
    ];
    foreach ($buttonDefaults as $key => $defaults) {
        $fromButtons = rac_reservation_ui_field_text($buttonsSaved, $key, $defaults['es'], $defaults['en']);
        $hasButtons = trim((string) ($buttonsSaved[$key] ?? '')) !== ''
            || trim((string) ($buttonsSaved[$key . '_en'] ?? '')) !== '';
        $fromResults = rac_reservation_ui_field_text($resultsSaved, $key, $defaults['es'], $defaults['en']);
        $value = $hasButtons ? $fromButtons : $fromResults;
        $out['buttons_' . $key] = $value;
        $out['results_' . $key] = $value;
    }

    return $out;
}

/** @return array<string, string> */
function rac_reservation_ui(): array
{
    static $resolved = null;
    if (is_array($resolved)) {
        return $resolved;
    }
    $homepage = [];
    if (class_exists('ContentService')) {
        try {
            $cs = new ContentService();
            $node = $cs->get('homepage', []);
            $homepage = is_array($node) ? $node : [];
        } catch (Throwable $e) {
            $homepage = [];
        }
    }
    $resolved = rac_reservation_ui_copy($homepage);

    return $resolved;
}

function rac_customer_copy(string $text): string
{
    if ($text === '' || !preg_match('/rentworks?/i', $text)) {
        return $text;
    }

    $map = [
        'la reserva no se confirma en RentWorks.' => 'la reserva no se confirma.',
        'the reservation is not confirmed in RentWorks.' => 'the reservation is not confirmed.',
        ' en RentWorks' => '',
        ' in RentWorks' => '',
        'RentWorks' => 'nuestro sistema',
        'rentworks' => 'nuestro sistema',
    ];
    return str_ireplace(array_keys($map), array_values($map), $text);
}

function rac_ui(string $key, string $fallback = ''): string
{
    $val = trim((string) (rac_reservation_ui()[$key] ?? ''));
    $out = $val !== '' ? $val : $fallback;

    return rac_customer_copy($out);
}

function rac_reservation_ui_script_tag(): void
{
    $copy = rac_reservation_ui();
    foreach ($copy as $k => $v) {
        if (is_string($v)) {
            $copy[$k] = rac_customer_copy($v);
        }
    }
    echo '<script>window.RAC_UI=' . json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
    echo 'window.racUi=function(k,f){var v=window.RAC_UI&&window.RAC_UI[k];return(v&&String(v).trim()!=="")?String(v):(f||"");};';
    echo 'window.racUiFormat=function(k,f,vars){var s=window.racUi(k,f);if(vars){Object.keys(vars).forEach(function(x){s=s.split("{"+x+"}").join(String(vars[x]));});}return s;};';
    echo '</script>' . "\n";
}
