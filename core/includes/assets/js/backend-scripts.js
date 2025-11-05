/*------------------------
Backend related javascript
------------------------*/

// Filter functions
function applyFilters() {
    var filterDate = document.getElementById('filter_date').value;
    var filterOrderId = document.getElementById('filter_order_id').value;

    // TODO: Implement filtering logic
    alert('Filtri applicati: Data=' + filterDate + ', ID=' + filterOrderId);
}

function clearFilters() {
    document.getElementById('filter_date').value = '';
    document.getElementById('filter_order_id').value = '';
    // TODO: Reset the order list
}

jQuery(document).ready(function($) {

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

        // Collect destination address data (from hidden fields)
        var destinatario = {
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

        // Validate destination data
        if (!destinatario.nome || !destinatario.indirizzo || !destinatario.cap || !destinatario.citta || !destinatario.prov) {
            alert('Dati destinatario incompleti');
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
                    $('body').prepend('<div id="cfeedback" class="notice notice-error is-dismissible"><p>' + issue + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
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
                    $(orderCard).find('.nordine').html('<div style="margin-top: 15px; padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; font-size: 13px;">✅ Spedizione nr ' + objJSON.id + ' inviata con successo</div>');
                    $(btninvia).prop('disabled', true).text('Inviato').css('opacity', '0.5');
                    $('html, body').animate({ scrollTop: 0 }, 300);
                    $('body').prepend('<div id="cfeedback" class="notice notice-success is-dismissible"><p>Spedizione inviata con successo. Se hai impostato come pagamento "Paga con Credito" la spedizione è già inviata. Altrimenti fai login nella tua area privata e paga la spedizione: Spedizione nr ' + objJSON.id + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
                    $(".notice-dismiss").click(function(e) {
                        var t = $("#cfeedback");
                        e.preventDefault();
                        t.fadeTo(100, 0, function() {
                            t.slideUp(100, function() {
                                t.remove();
                            });
                        });
                    });
                }
            },
            error: function(xhr, status, error) {
                alert('Errore di connessione: ' + error);
            }
        });
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

});

