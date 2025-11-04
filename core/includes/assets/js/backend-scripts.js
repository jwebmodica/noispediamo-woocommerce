/*------------------------
Backend related javascript
------------------------*/
jQuery(document).ready(function($) {

    // Handle add package button
    $(document).on('click', '.aggiungi-pacco', function(e) {
        e.preventDefault();
        var container = $(this).closest('td').find('.pacchi-container');
        var paccoCount = container.find('.pacco-row').length;
        var newIndex = paccoCount;

        // Clone the first package row
        var newPacco = container.find('.pacco-row:first').clone();

        // Update the index and clear values
        newPacco.attr('data-pacco-index', newIndex);
        newPacco.find('strong').text('Pacco #' + (newIndex + 1));
        newPacco.find('input').val('');
        newPacco.find('.rimuovi-pacco').show();

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
        var $tr = $(this).closest('tr');
        var ordineid = $tr.find('.rigaordine').text();
        var ritiro = $tr.find('.ritiro').val();

        // Collect destination address data
        var destinatario = {
            nome: $tr.find('.dest-nome').val().trim(),
            indirizzo: $tr.find('.dest-indirizzo').val().trim(),
            civico: $tr.find('.dest-civico').val().trim(),
            cap: $tr.find('.dest-cap').val().trim(),
            citta: $tr.find('.dest-citta').val().trim(),
            prov: $tr.find('.dest-prov').val().trim(),
            email: $tr.find('.dest-email').val().trim(),
            telefono: $tr.find('.dest-telefono').val().trim(),
            note: $tr.find('.dest-note').val().trim()
        };

        // Validate destination data
        if (!destinatario.nome || !destinatario.indirizzo || !destinatario.cap || !destinatario.citta || !destinatario.prov) {
            alert('Compila tutti i campi obbligatori del destinatario (Nome, Indirizzo, CAP, Città, Provincia)');
            return;
        }

        // Collect all packages for this order
        var pacchi = [];
        var valid = true;
        $tr.find('.pacco-row').each(function() {
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
                    $("#soprafeedback").after('<div id="cfeedback" class="notice notice-error is-dismissible"><p>' + issue + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
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
                    var trordine = "#trordine-" + objJSON.idordine;
                    var btninvia = "#invia-" + objJSON.idordine;
                    $(trordine).find('.nordine').append(objJSON.id);
                    $(btninvia).remove();
                    $("#soprafeedback").after('<div id="cfeedback" class="notice notice-success is-dismissible"><p>Spedizione inviata con successo. Se hai impostato come pagamento "Paga con Credito" la spedizione è già inviata. Altrimenti fai login nella tua area privata e paga la spedizione: Spedizione nr ' + objJSON.id + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
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
    
    
    $( '.my-datepicker' ).datepicker({
        dateFormat: "dd/mm/yy",
        changeMonth: true,
        changeYear: true,
        minDate: "today" + 1,
        setDate: 1,
        beforeShowDay: $.datepicker.noWeekends
        });
} );

});

