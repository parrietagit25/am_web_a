/**
 * Automarket Frontend Controller
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Elements references
    const toggleReturnBranch = document.getElementById('toggleReturnBranch');
    const returnLocationWrapper = document.getElementById('returnLocationWrapper');
    const returnLocationSelect = document.getElementById('returnLocation');

    const toggleCoupon = document.getElementById('toggleCoupon');
    const couponCodeWrapper = document.getElementById('couponCodeWrapper');
    const promoCodeInput = document.getElementById('promoCode');

    const reservationForm = document.getElementById('reservationSearchForm');
    const searchResultsSection = document.getElementById('searchResultsSection');
    const resultsContainer = document.getElementById('resultsVehiclesContainer');
    
    // Set default dates: pickup tomorrow, return 3 days after tomorrow
    const pickupDateInput = document.getElementById('pickupDate');
    const returnDateInput = document.getElementById('returnDate');
    
    if (pickupDateInput && returnDateInput) {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        const returnDate = new Date(tomorrow);
        returnDate.setDate(returnDate.getDate() + 3);
        
        pickupDateInput.value = formatDate(tomorrow);
        pickupDateInput.min = formatDate(today);
        returnDateInput.value = formatDate(returnDate);
        returnDateInput.min = formatDate(tomorrow);

        // Adjust return date min constraint when pickup changes
        pickupDateInput.addEventListener('change', function() {
            const chosenPickup = new Date(pickupDateInput.value);
            const chosenReturn = new Date(returnDateInput.value);
            
            const nextDay = new Date(chosenPickup);
            nextDay.setDate(nextDay.getDate() + 1);
            returnDateInput.min = formatDate(nextDay);
            
            if (chosenReturn <= chosenPickup) {
                returnDateInput.value = formatDate(nextDay);
            }
        });
    }

    /**
     * Date formatter (YYYY-MM-DD)
     */
    function formatDate(date) {
        const yyyy = date.getFullYear();
        let mm = date.getMonth() + 1;
        let dd = date.getDate();
        
        if (mm < 10) mm = '0' + mm;
        if (dd < 10) dd = '0' + dd;
        
        return `${yyyy}-${mm}-${dd}`;
    }

    // 1. Toggle Different Return Location Dropdown
    if (toggleReturnBranch) {
        toggleReturnBranch.addEventListener('change', function() {
            if (this.checked) {
                returnLocationWrapper.classList.remove('d-none');
                returnLocationSelect.setAttribute('required', 'required');
            } else {
                returnLocationWrapper.classList.add('d-none');
                returnLocationSelect.removeAttribute('required');
                returnLocationSelect.value = '';
            }
        });
    }

    // 2. Toggle Promo Code Field
    if (toggleCoupon) {
        toggleCoupon.addEventListener('change', function() {
            if (this.checked) {
                couponCodeWrapper.classList.remove('d-none');
            } else {
                couponCodeWrapper.classList.add('d-none');
                promoCodeInput.value = '';
            }
        });
    }

    // 3. Handle Reservation Form Submission & Simulated API Connect
    if (reservationForm) {
        reservationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Perform basic Bootstrap validations
            if (!reservationForm.checkValidity()) {
                e.stopPropagation();
                reservationForm.classList.add('was-validated');
                return;
            }

            reservationForm.classList.add('was-validated');

            // Gather inputs for API payload
            const payload = {
                locationCode: document.getElementById('pickupLocation').value,
                returnLocationCode: toggleReturnBranch.checked ? returnLocationSelect.value : document.getElementById('pickupLocation').value,
                pickupDate: pickupDateInput.value,
                pickupTime: document.getElementById('pickupTime').value,
                returnDate: returnDateInput.value,
                returnTime: document.getElementById('returnTime').value,
                age: document.getElementById('ageCheck').checked ? '25' : 'under25',
                promoCode: toggleCoupon.checked ? promoCodeInput.value : ''
            };

            console.log('Sending search parameters payload: ', payload);

            // Show a premium full-screen loader overlay dynamically
            let loaderOverlay = document.getElementById('premiumLoaderOverlay');
            if (!loaderOverlay) {
                loaderOverlay = document.createElement('div');
                loaderOverlay.id = 'premiumLoaderOverlay';
                loaderOverlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white';
                loaderOverlay.style.zIndex = '99999';
                loaderOverlay.style.backgroundColor = 'rgba(8, 16, 38, 0.9)';
                loaderOverlay.style.backdropFilter = 'blur(6px)';
                loaderOverlay.innerHTML = `
                    <div class="spinner-border text-danger" style="width: 3.5rem; height: 3.5rem;" role="status">
                        <span class="visually-hidden">Buscando...</span>
                    </div>
                    <h3 class="mt-4 fw-bold font-montserrat text-center">Consultando Disponibilidad</h3>
                    <p class="text-secondary-light font-poppins text-sm text-center">Buscando los mejores vehículos de nuestra flota en tiempo real...</p>
                `;
                document.body.appendChild(loaderOverlay);
            } else {
                loaderOverlay.classList.remove('d-none');
            }

            // Run fetch to the local API endpoint
            fetch('/api/disponibilidad.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error("HTTP error " + res.status);
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    // Save response and criteria to sessionStorage
                    sessionStorage.setItem('searchResults', JSON.stringify(data));
                    sessionStorage.setItem('searchCriteria', JSON.stringify(payload));
                    // Redirect to results page
                    window.location.href = '/resultados.php';
                } else {
                    throw new Error(data.message || "Error al procesar la solicitud.");
                }
            })
            .catch(err => {
                console.error('Error fetching availability:', err);
                if (loaderOverlay) {
                    loaderOverlay.classList.add('d-none');
                }
                alert("Lo sentimos, ocurrió un problema al consultar la disponibilidad. Por favor, verifica las fechas e intenta de nuevo. Detalle: " + err.message);
            });
        });
    }

    /**
     * Render mock vehicles when searching locally
     */
    function simulateSearchResults(payload) {
        // Fetch static vehicle list mock models
        const mockVehicles = [
            { name: 'Toyota Hilux 4x4 Doble Cabina', category: 'Pick Up', passengers: 5, ac: true, transmission: 'Manual', price: 55.00, img: 'hilux.jpg' },
            { name: 'Hyundai Accent Accent GL', category: 'Sedanes', passengers: 5, ac: true, transmission: 'Automática', price: 29.99, img: 'accent.jpg' },
            { name: 'Toyota RAV4 Active SUV', category: 'SUV', passengers: 5, ac: true, transmission: 'Automática', price: 45.50, img: 'rav4.jpg' },
            { name: 'Hyundai H1 Cargo Panel', category: 'Comerciales', passengers: 3, ac: true, transmission: 'Manual', price: 60.00, img: 'h1.jpg' },
            { name: 'Kia Picanto Hatchback', category: 'Promociones', passengers: 4, ac: true, transmission: 'Manual', price: 19.99, img: 'picanto.jpg' },
            { name: 'Mitsubishi L200 Pick-up Sportero', category: 'Pick Up', passengers: 5, ac: true, transmission: 'Automática', price: 59.99, img: 'l200.jpg' },
            { name: 'Toyota Corolla Sedan', category: 'Sedanes', passengers: 5, ac: true, transmission: 'Automática', price: 35.00, img: 'corolla.jpg' },
            { name: 'Suzuki Jimny 4x4 Offroad', category: 'SUV', passengers: 4, ac: true, transmission: 'Automática', price: 42.00, img: 'jimny.jpg' }
        ];

        // Fill summarized specs box
        const searchSummaryText = document.getElementById('searchSummaryText');
        if (searchSummaryText) {
            const locText = payload.locationCode;
            const retText = payload.returnLocationCode !== locText ? ` (Devolución: ${payload.returnLocationCode})` : '';
            searchSummaryText.innerText = `Retiro en ${locText}${retText} | del ${payload.pickupDate} al ${payload.returnDate}`;
        }

        renderVehicleCards(mockVehicles);

        // Add filter behaviors
        setupCategoryFilters(mockVehicles);
    }

    /**
     * Maps vehicles objects list to html cards grid layout
     */
    function renderVehicleCards(vehicles) {
        resultsContainer.innerHTML = '';
        
        if (vehicles.length === 0) {
            resultsContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
                    <h5 class="mt-3 text-navy fw-bold">No se encontraron vehículos disponibles</h5>
                    <p class="text-muted">Prueba cambiando las fechas o seleccionando otra sucursal.</p>
                </div>
            `;
            return;
        }

        vehicles.forEach(vehicle => {
            const cardCol = document.createElement('div');
            cardCol.className = 'col-lg-4 col-md-6 col-12 d-flex mb-4';
            cardCol.innerHTML = `
                <div class="card vehicle-card border-0 shadow-sm rounded-4 w-100 flex-column justify-content-between overflow-hidden transition-all duration-300">
                    <span class="category-badge position-absolute bg-white px-3 py-1 text-navy rounded-pill fw-bold shadow-sm top-3 start-3 text-uppercase z-index-2">
                        ${vehicle.category}
                    </span>
                    <div class="card-image-wrapper bg-light-gray p-4 text-center position-relative">
                        <div class="car-illustration-placeholder py-4">
                            <i class="bi bi-car-front text-theme opacity-25" style="font-size: 5rem;"></i>
                        </div>
                    </div>
                    <div class="card-body px-4 py-3">
                        <h4 class="card-title fw-bold text-navy mb-3">${vehicle.name}</h4>
                        <div class="specs-grid d-flex flex-wrap gap-3 mb-4 text-muted">
                            <div class="spec-item d-flex align-items-center gap-1">
                                <i class="bi bi-people-fill text-theme-secondary"></i>
                                <span>${vehicle.passengers} Pasajeros</span>
                            </div>
                            <div class="spec-item d-flex align-items-center gap-1">
                                <i class="bi bi-snow text-theme-secondary"></i>
                                <span>${vehicle.ac ? 'A/C' : 'No A/C'}</span>
                            </div>
                            <div class="spec-item d-flex align-items-center gap-1">
                                <i class="bi bi-gear-wide-connected text-theme-secondary"></i>
                                <span>${vehicle.transmission}</span>
                            </div>
                        </div>
                        <hr class="border-light-gray my-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="price-block">
                                <span class="text-muted text-sm d-block font-poppins">Precio diario</span>
                                <span class="fs-3 fw-bold text-navy font-poppins">$${vehicle.price.toFixed(2)}</span>
                                <span class="text-muted text-sm font-poppins">USD</span>
                            </div>
                            <button class="btn btn-theme px-4 py-2 rounded-pill fw-bold text-white shadow-sm transition-all" onclick="alert('Iniciando reserva para: ${vehicle.name.replace(/'/g, "\\'")} en entorno simulado.')">
                                Reservar <i class="bi bi-arrow-right-short ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            resultsContainer.appendChild(cardCol);
        });
    }

    /**
     * Category filtering toggle links script
     */
    function setupCategoryFilters(vehicles) {
        const filterLinks = document.querySelectorAll('.filter-category-btn');
        filterLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Toggle active filter button states
                filterLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                const selectedCategory = this.getAttribute('data-category');
                
                if (selectedCategory === 'all') {
                    renderVehicleCards(vehicles);
                } else {
                    const filtered = vehicles.filter(v => v.category.toLowerCase() === selectedCategory.toLowerCase());
                    renderVehicleCards(filtered);
                }
            });
        });
    }
    
    // Dynamic back-to-top results modifier button hook
    const modifySearchBtn = document.getElementById('modifySearchBtn');
    if (modifySearchBtn) {
        modifySearchBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // 4. Handle Corporate Leasing Form Submission
    const leasingLeadForm = document.getElementById('leasingLeadForm');
    if (leasingLeadForm) {
        leasingLeadForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const feedbackDiv = document.getElementById('form-feedback');
            const submitBtn = document.getElementById('btn-leasing-submit');
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');

            // Reset feedback
            if (feedbackDiv) {
                feedbackDiv.classList.add('d-none');
                feedbackDiv.className = 'form-feedback mt-3 text-center';
                feedbackDiv.innerText = '';
            }

            // Perform HTML5 validations
            if (!leasingLeadForm.checkValidity()) {
                e.stopPropagation();
                leasingLeadForm.classList.add('was-validated');
                return;
            }

            // Additional Custom Validation
            const industria = document.getElementById('industria').value;
            const tipoAuto = document.getElementById('tipo_auto').value;

            if (industria === '' || industria === 'Seleccione Uno') {
                alert("Por favor selecciona una industria válida.");
                return;
            }
            if (tipoAuto === '' || tipoAuto === 'Seleccione Uno') {
                alert("Por favor selecciona un tipo de auto válido.");
                return;
            }

            leasingLeadForm.classList.add('was-validated');

            // Disable button and show loader spinner inside button
            if (submitBtn) submitBtn.setAttribute('disabled', 'disabled');
            if (btnSpinner) btnSpinner.classList.remove('d-none');
            if (btnText) btnText.innerText = 'ENVIANDO...';

            // Gather inputs for payload
            const payload = {
                empresa: document.getElementById('empresa').value,
                ruc: document.getElementById('ruc').value,
                industria: industria,
                cantidad_vehiculos: parseInt(document.getElementById('cantidad_vehiculos').value),
                contacto: document.getElementById('contacto').value,
                celular: document.getElementById('celular').value,
                email: document.getElementById('email').value,
                fecha_tentativa: document.getElementById('fecha_tentativa').value,
                tipo_auto: tipoAuto,
                comentarios: document.getElementById('comentarios').value
            };

            // Post payload to dispatcher endpoint
            fetch('/api/enviar-leasing-pipedrive.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error("HTTP error " + res.status);
                }
                return res.json();
            })
            .then(data => {
                // Restore button states
                if (submitBtn) submitBtn.removeAttribute('disabled');
                if (btnSpinner) btnSpinner.classList.add('d-none');
                if (btnText) btnText.innerText = 'ENVIAR SOLICITUD';

                if (data.success) {
                    // Show green success alert
                    if (feedbackDiv) {
                        feedbackDiv.classList.remove('d-none');
                        feedbackDiv.classList.add('success');
                        feedbackDiv.innerText = 'Solicitud enviada correctamente. Un asesor se pondrá en contacto contigo.';
                    }
                    // Reset inputs
                    leasingLeadForm.reset();
                    leasingLeadForm.classList.remove('was-validated');
                } else {
                    throw new Error(data.message || "Error al procesar el envío.");
                }
            })
            .catch(err => {
                console.error('Error submitting corporate lead:', err);
                
                // Restore button states
                if (submitBtn) submitBtn.removeAttribute('disabled');
                if (btnSpinner) btnSpinner.classList.add('d-none');
                if (btnText) btnText.innerText = 'ENVIAR SOLICITUD';

                // Show red error alert
                if (feedbackDiv) {
                    feedbackDiv.classList.remove('d-none');
                    feedbackDiv.classList.add('error');
                    feedbackDiv.innerText = 'No se pudo enviar la solicitud. Intente nuevamente.';
                }
            });
        });
    }

    // 5. Rent A Car Categories Fleet Carousel Slider
    const fleetTrack = document.getElementById('fleet-carousel-track');
    const fleetPrevBtn = document.getElementById('fleet-prev-btn');
    const fleetNextBtn = document.getElementById('fleet-next-btn');

    if (fleetTrack && fleetPrevBtn && fleetNextBtn) {
        const fleetItems = fleetTrack.querySelectorAll('.fleet-carousel-item');
        const totalFleetItems = fleetItems.length;
        let currentFleetIndex = 0;

        // Parse behavior settings from data attributes
        const autoplay = fleetTrack.getAttribute('data-autoplay') === 'true';
        const direction = fleetTrack.getAttribute('data-direction') || 'right';
        const interval = parseInt(fleetTrack.getAttribute('data-interval') || '3000', 10);
        let autoplayTimer = null;

        function getVisibleFleetItems() {
            if (window.innerWidth >= 992) return 3;
            if (window.innerWidth >= 576) return 2;
            return 1;
        }

        function updateFleetSlider() {
            const visibleItems = getVisibleFleetItems();
            const maxIndex = totalFleetItems - visibleItems;
            
            // Adjust current index bounds
            if (currentFleetIndex > maxIndex) currentFleetIndex = maxIndex;
            if (currentFleetIndex < 0) currentFleetIndex = 0;

            const itemWidth = 100 / visibleItems;
            const translateX = -currentFleetIndex * itemWidth;
            fleetTrack.style.transform = `translateX(${translateX}%)`;
        }

        fleetNextBtn.addEventListener('click', function() {
            const visibleItems = getVisibleFleetItems();
            const maxIndex = totalFleetItems - visibleItems;
            
            if (currentFleetIndex >= maxIndex) {
                currentFleetIndex = 0; // Wrap around to start
            } else {
                currentFleetIndex++;
            }
            updateFleetSlider();
        });

        fleetPrevBtn.addEventListener('click', function() {
            const visibleItems = getVisibleFleetItems();
            const maxIndex = totalFleetItems - visibleItems;
            
            if (currentFleetIndex <= 0) {
                currentFleetIndex = maxIndex; // Wrap around to end
            } else {
                currentFleetIndex--;
            }
            updateFleetSlider();
        });

        function startAutoplay() {
            if (!autoplay) return;
            stopAutoplay();
            autoplayTimer = setInterval(function() {
                if (direction === 'right') {
                    fleetNextBtn.click();
                } else {
                    fleetPrevBtn.click();
                }
            }, interval);
        }

        function stopAutoplay() {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        }

        // Update layout on window resize
        window.addEventListener('resize', updateFleetSlider);
        
        // Initial build & start timers
        updateFleetSlider();
        startAutoplay();

        // Pause autoplay on hover
        const carouselWrapper = document.querySelector('.fleet-carousel-wrapper');
        if (carouselWrapper) {
            carouselWrapper.addEventListener('mouseenter', stopAutoplay);
            carouselWrapper.addEventListener('mouseleave', startAutoplay);
        }
    }
});
