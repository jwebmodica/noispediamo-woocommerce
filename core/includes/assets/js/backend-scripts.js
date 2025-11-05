/*------------------------
Backend related javascript
------------------------*/

// Sanitize numeric input: replace comma with dot, remove non-numeric characters except dot
function sanitizeNumericInput(value) {
    if (!value) return '';
    // Convert to string, replace comma with dot, keep only numbers and dots
    return value.toString().replace(',', '.').replace(/[^\d.]/g, '');
}

// Filter functions
function applyFilters() {
    var filterDate = document.getElementById('filter_date').value;
    var filterOrderId = document.getElementById('filter_order_id').value.trim();

    // Get all order cards
    var cards = document.querySelectorAll('.order-card');
    var visibleCount = 0;

    cards.forEach(function(card) {
        var cardOrderId = card.getAttribute('data-order-id');
        var cardOrderDate = card.getAttribute('data-order-date');

        var showCard = true;

        // Filter by order ID (partial match)
        if (filterOrderId !== '' && !cardOrderId.includes(filterOrderId)) {
            showCard = false;
        }

        // Filter by date (exact match in d/m/Y format)
        if (filterDate !== '') {
            // Convert date input (Y-m-d) to d/m/Y format
            var dateParts = filterDate.split('-');
            var formattedFilterDate = dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0];

            if (cardOrderDate !== formattedFilterDate) {
                showCard = false;
            }
        }

        // Show or hide the card
        if (showCard) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Show message if no orders match
    if (visibleCount === 0) {
        var container = document.querySelector('.orders-container');
        if (!document.getElementById('no-results-message')) {
            var message = document.createElement('div');
            message.id = 'no-results-message';
            message.style.cssText = 'background: white; border-radius: 8px; padding: 40px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);';
            message.innerHTML = '<p style="font-size: 16px; color: #666;">🔍 Nessun ordine trovato con i filtri selezionati</p>';
            if (container) {
                container.appendChild(message);
            }
        }
    } else {
        // Remove no results message if exists
        var noResultsMsg = document.getElementById('no-results-message');
        if (noResultsMsg) {
            noResultsMsg.remove();
        }
    }
}

function clearFilters() {
    document.getElementById('filter_date').value = '';
    document.getElementById('filter_order_id').value = '';

    // Show all order cards
    var cards = document.querySelectorAll('.order-card');
    cards.forEach(function(card) {
        card.style.display = 'block';
    });

    // Remove no results message if exists
    var noResultsMsg = document.getElementById('no-results-message');
    if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

jQuery(document).ready(function($) {

    // Toggle address edit form
    $(document).on('click', '.toggle-edit-address', function(e) {
        e.preventDefault();
        var $card = $(this).closest('[id^="order-card-"]');
        var $editForm = $card.find('.address-edit-form');
        var $display = $card.find('.address-display');

        $editForm.slideToggle(300);

        // Change button text
        if ($editForm.is(':visible')) {
            $(this).text('Chiudi');
        } else {
            $(this).text('Modifica');
        }
    });

    // Handle add package button
    $(document).on('click', '.aggiungi-pacco', function(e) {
        e.preventDefault();
        var container = $(this).siblings('.pacchi-container');
        var paccoCount = container.find('.pacco-row').length;
        var newIndex = paccoCount;

        // Clone the first package row
        var newPacco = container.find('.pacco-row:first').clone();

        // Update the index and clear values
        newPacco.attr('data-pacco-index', newIndex);
        newPacco.find('strong').text('Pacco #' + (newIndex + 1));
        newPacco.find('input').val('');
        newPacco.find('.rimuovi-pacco').show();

        // Add spacing between packages
        newPacco.css('margin-top', '10px');
        newPacco.css('padding-top', '10px');
        newPacco.css('border-top', '1px solid #ddd');

        // Append to container
        container.append(newPacco);

        // Update remove button visibility
        updateRemoveButtons(container);
    });

    // Handle remove package button
    $(document).on('click', '.rimuovi-pacco', function(e) {
        e.preventDefault();
        var container = $(this).closest('.pacchi-container');
        $(this).closest('.pacco-row').remove();

        // Renumber remaining packages
        container.find('.pacco-row').each(function(index) {
            $(this).attr('data-pacco-index', index);
            $(this).find('strong').text('Pacco #' + (index + 1));
        });

        // Update remove button visibility
        updateRemoveButtons(container);
    });

    // Function to update remove button visibility
    function updateRemoveButtons(container) {
        var paccoCount = container.find('.pacco-row').length;
        if (paccoCount === 1) {
            container.find('.rimuovi-pacco').hide();
        } else {
            container.find('.rimuovi-pacco').show();
        }
    }

    $("#refreshcorrieri").click(function(e) {
        e.preventDefault();

        $.ajax({
            type: "POST",
            url: cspedisci.ajax_url,
            data: {
                action: 'cspedisci_ottieni_corrieri',
                nonce: cspedisci.nonce_ottieni_corrieri,
                email: $("#email").val(),
                password: $("#password").val()
            },
            success: function(response) {
                if (response.success) {
                    alert("Corrieri aggiornati con successo. La pagina sarà ricaricata.");
                    location.reload(true);
                } else {
                    alert('Errore: ' + (response.data && response.data.issue ? response.data.issue : 'Errore sconosciuto'));
                }
            },
            error: function(xhr, status, error) {
                alert('Errore di connessione: ' + error);
            }
        });
    });




    $(document).on('click', '.invia-ordine-btn', function(e) {
        e.preventDefault();
        var $card = $(this).closest('[id^="order-card-"]');
        var ordineid = $card.find('.rigaordine').val();
        var ritiro = $card.find('.ritiro').val();

        // Check if user edited the address fields (edit fields exist and have values)
        var useEditedFields = $card.find('.edit-nome').length > 0;

        var destinatario;
        if (useEditedFields) {
            // Use edited values from the edit form
            var nome = $card.find('.edit-nome').val().trim();
            var cognome = $card.find('.edit-cognome').val().trim();
            var nomeCompleto = (nome + ' ' + cognome).trim();

            destinatario = {
                nome: nomeCompleto,
                indirizzo: $card.find('.edit-indirizzo').val().trim(),
                civico: $card.find('.edit-civico').val().trim(),
                cap: $card.find('.edit-cap').val().trim(),
                citta: $card.find('.edit-citta').val().trim(),
                prov: $card.find('.edit-prov').val().trim(),
                email: $card.find('.edit-email').val().trim(),
                telefono: $card.find('.edit-telefono').val().trim(),
                note: $card.find('.edit-note').val().trim()
            };
        } else {
            // Fallback to hidden fields (shouldn't happen with new layout, but just in case)
            destinatario = {
                nome: $card.find('.dest-nome').val().trim(),
                indirizzo: $card.find('.dest-indirizzo').val().trim(),
                civico: $card.find('.dest-civico').val().trim(),
                cap: $card.find('.dest-cap').val().trim(),
                citta: $card.find('.dest-citta').val().trim(),
                prov: $card.find('.dest-prov').val().trim(),
                email: $card.find('.dest-email').val().trim(),
                telefono: $card.find('.dest-telefono').val().trim(),
                note: $card.find('.dest-note').val().trim()
            };
        }

        // Validate destination data
        if (!destinatario.nome || !destinatario.indirizzo || !destinatario.cap || !destinatario.citta || !destinatario.prov) {
            alert('Dati destinatario incompleti. Compila tutti i campi obbligatori (Nome, Indirizzo, CAP, Città, Provincia).');
            return;
        }

        // Validate email
        if (!destinatario.email || !destinatario.email.includes('@')) {
            alert('Inserisci un indirizzo email valido.');
            return;
        }

        // Validate phone
        if (!destinatario.telefono) {
            alert('Inserisci un numero di telefono.');
            return;
        }

        // Collect all packages for this order
        var pacchi = [];
        var valid = true;
        $card.find('.pacco-row').each(function() {
            var peso = $(this).find('.peso').val();
            var alt = $(this).find('.alt').val();
            var largh = $(this).find('.largh').val();
            var prof = $(this).find('.prof').val();

            // Sanitize inputs: replace comma with dot, remove non-numeric characters except dot
            peso = sanitizeNumericInput(peso);
            alt = sanitizeNumericInput(alt);
            largh = sanitizeNumericInput(largh);
            prof = sanitizeNumericInput(prof);

            // Validate package data
            if (!peso || !alt || !largh || !prof || parseFloat(peso) <= 0 || parseFloat(alt) <= 0 || parseFloat(largh) <= 0 || parseFloat(prof) <= 0) {
                valid = false;
                return false; // break the loop
            }

            pacchi.push({
                weight: peso,
                height: alt,
                width: largh,
                length: prof
            });
        });

        if (!valid) {
            alert('Specifica le dimensioni corrette per tutti i pacchi');
            return;
        }

        if (!ritiro) {
            alert('Indicare una data di ritiro valida');
            return;
        }

        $.ajax({
            type: "POST",
            url: cspedisci.ajax_url,
            data: {
                action: 'cspedisci_invia_ordine',
                nonce: cspedisci.nonce_invia_ordine,
                idordine: ordineid,
                destinatario: JSON.stringify(destinatario),
                pacchi: JSON.stringify(pacchi),
                ritiro: ritiro
            },
            success: function(response) {
                $("#cfeedback").remove();

                if (!response.success) {
                    // Error case
                    var issue = response.data && response.data.issue ? response.data.issue : 'Errore sconosciuto';
                    $('html, body').animate({ scrollTop: 0 }, 300);
                    $('.wrap').prepend('<div id="cfeedback" class="notice notice-error is-dismissible" style="margin: 0 0 20px 0; padding: 15px; border-left: 4px solid #dc3232; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"><p style="margin: 0;"><strong>Errore:</strong> ' + issue + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
                    $(".notice-dismiss").click(function(e) {
                        var t = $("#cfeedback");
                        e.preventDefault();
                        t.fadeTo(100, 0, function() {
                            t.slideUp(100, function() {
                                t.remove();
                            });
                        });
                    });
                    console.log(issue);
                } else {
                    // Success case
                    var objJSON = response.data;
                    var orderCard = "#order-card-" + objJSON.idordine;
                    var btninvia = "#invia-" + objJSON.idordine;
                    var trackingUrl = 'https://ordini.noispediamo.it/tracking/' + objJSON.id;

                    // Replace the entire card content with success message
                    $(orderCard).html(
                        '<div style="padding: 30px; text-align: center;">' +
                            '<div style="font-size: 48px; margin-bottom: 15px;">✅</div>' +
                            '<h3 style="color: #155724; margin-bottom: 10px;">Spedizione Inviata con Successo!</h3>' +
                            '<p style="font-size: 14px; color: #666; margin-bottom: 15px;">Spedizione nr <strong>' + objJSON.id + '</strong></p>' +
                            '<div style="background: #f0f9ff; border: 1px solid #b3d9ff; border-radius: 6px; padding: 15px; margin-bottom: 15px;">' +
                                '<p style="margin: 0; font-size: 13px; color: #333;">📦 <strong>Traccia la spedizione:</strong></p>' +
                                '<a href="' + trackingUrl + '" target="_blank" style="display: inline-block; margin-top: 8px; padding: 8px 16px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; font-size: 13px;">Apri Tracking →</a>' +
                            '</div>' +
                            '<p style="font-size: 12px; color: #666; margin: 0;">Se hai impostato "Paga con Credito", la spedizione è già attiva. Altrimenti accedi alla tua area privata NoiSpediamo per completare il pagamento.</p>' +
                        '</div>'
                    );

                    // Add fade-out animation and remove card after 5 seconds
                    setTimeout(function() {
                        $(orderCard).fadeOut(500, function() {
                            $(this).remove();

                            // Check if there are any remaining orders
                            if ($('[id^="order-card-"]').length === 0) {
                                $('.orders-container').html(
                                    '<div style="background: white; border-radius: 8px; padding: 40px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">' +
                                        '<p style="font-size: 16px; color: #666;">📦 Nessun ordine da spedire</p>' +
                                    '</div>'
                                );
                            }
                        });
                    }, 5000);
                }
            },
            error: function(xhr, status, error) {
                alert('Errore di connessione: ' + error);
            }
        });
    });

    // Sanitize numeric inputs in real-time (package weight and dimensions)
    $(document).on('input', '.peso, .alt, .largh, .prof', function() {
        var $input = $(this);
        var sanitized = sanitizeNumericInput($input.val());
        if ($input.val() !== sanitized) {
            $input.val(sanitized);
        }
    });

    // Initialize datepicker for pickup date
    $( '.my-datepicker' ).datepicker({
        dateFormat: "dd/mm/yy",
        changeMonth: true,
        changeYear: true,
        minDate: +1,  // Tomorrow (no same-day pickup)
        maxDate: "+14D",  // Max 14 days in advance
        firstDay: 1,  // Week starts on Monday
        beforeShowDay: $.datepicker.noWeekends,  // Disable Saturdays and Sundays
        onSelect: function(dateText) {
            $(this).trigger('change');
        }
    });
});
