$(document).ready(function () {
    if ($('input[type=radio][name=smartbill_sync_stock]').length) {
        console.log($('input[type=radio][name=smartbill_sync_stock]:checked').val());
        if ($('input[type=radio][name=smartbill_sync_stock]:checked').val() == '1') {
            $('.token_details').removeClass('smrt_greyed_out_text');
            $('#smartbill_used_stock').prop("disabled", false);
        } else {
            $('.token_details').addClass('smrt_greyed_out_text');
            $('#smartbill_used_stock').prop("disabled", true);
        }

        $('input[type=radio][name=smartbill_sync_stock]').change(function () {
            if (this.value == '1') {
                $('.token_details').removeClass('smrt_greyed_out_text');
                $('#smartbill_used_stock').prop("disabled", false);
            } else {
                $('.token_details').addClass('smrt_greyed_out_text');
                $('#smartbill_used_stock').prop("disabled", true);
            }
        });
    }

    if ($('input[type=radio][name=smartbill_send_mail_with_document]').length) {
        if ($('input[name=smartbill_send_mail_with_document]:checked').val() === '0') {
            $('.issue-document').hide();
        }
        $('input[type=radio][name=smartbill_send_mail_with_document]').change(function () {
            if ($('input[name=smartbill_send_mail_with_document]:checked').val() === '0') {
                $('.issue-document').hide();
            } else {
                $('.issue-document').show();
            }
        });
    }

    //hide setting order status
    if ($('input[type=radio][name=smartbill_automatically_issue_document]').length) {
        if ($('input[name=smartbill_automatically_issue_document]:checked').val() === '0') {
            $('.order_status').hide();
        }
        $('input[type=radio][name=smartbill_automatically_issue_document]').change(function () {
            if ($('input[name=smartbill_automatically_issue_document]:checked').val() === '0') {
                $('.order_status').hide();
            } else {
                $('.order_status').show();
            }
        });
    }

    if ($('#smartbill_mail').length) {
        $('#smartbill_mail').on('click', function (e) {
            e.preventDefault();
            var api_call = $(this).attr('href');
            call_mail_doc(api_call);
        });
    }

    // if in order info
    if ($('#smartbill_issue').length) {
        $('#smartbill_issue').on('click', function (e) {
            e.preventDefault();

            $(this).addClass('disabled');
            var loading = '<span class="btn pull-right" id="smrt_loading"><i class="fa fa-gear"></i></span>';
            $(this).parent().append(loading);
            $('.smrt-doc').hide();

            var api_call = $(this).attr('href');

            if ($(this).hasClass('reissue')) {
                var attention_text = document.createElement("div");
                attention_text.innerHTML = "Doriti remiterea documentului in SmartBill Cloud?<br/>Va recomandam sa anulati sau sa stergeti documentul emis anterior in SmartBill Cloud inainte de remitere.";
                swal({
                    title: "Atentie!",
                    content: attention_text,
                    icon: 'warning',
                    buttons: [true, true]
                }).then(function (result) {
                    if (!result) {
                        $('#smartbill_issue').removeClass('disabled');
                        $('#smrt_loading').remove();
                        $('.smrt-doc').show();
                        return false;
                    }
                    call_issue_doc(api_call);
                });
            } else {
                call_issue_doc(api_call);
            }
        });
    }

    function IsJsonString(str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    }
    function call_mail_doc(api_call) {
        var info_text = document.createElement("div");
        info_text.innerHTML = "Va rugam asteptati raspunsul serverului...";
        swal({
            title: 'Se incarca!',
            content: info_text,
            icon: 'info',
            buttons: [false, false]
        });
        $.ajax({
            url: api_call,
            success: function (data) {
                if (IsJsonString(data)) {
                    data = JSON.parse(data);
                } else {
                    var new_data = {};
                    new_data.message = data;
                    new_data.error = 'Not JSON';
                    data = new_data;
                }
                if (typeof data.error !== 'undefined' && !data.status) {
                    if (data.message == '') {
                        data.message = 'Verificati setarile modulului.';
                    }
                    Toastify({
                        text: "Eroare! " + data.message.replace("Smart Bill", "SmartBill").replace("SmartBill Cloud", "SmartBill"), duration: -1, newWindow: false, close: true, gravity: "top", position: 'center', backgroundColor: "#EF4136", stopOnFocus: true,
                    }).showToast()
                } else {
                    Toastify({
                        text: data.message.replace("Smart Bill", "SmartBill").replace("SmartBill Cloud", "SmartBill"), duration: 3000, newWindow: false, close: true, gravity: "top", position: 'center', backgroundColor: "#00A14B", stopOnFocus: true,
                    }).showToast();
                }
                swal.close();
            },
            error: function (data) {
                Toastify({
                    text: "Eroare! " + data.responseText.replace("Smart Bill", "SmartBill").replace("SmartBill Cloud", "SmartBill"), duration: -1, newWindow: false, close: true, gravity: "top", position: 'center', backgroundColor: "#EF4136", stopOnFocus: true,
                }).showToast();
                swal.close();

            }
        });
    }

    function call_issue_doc(api_call) {
        var info_text = document.createElement("div");
        info_text.innerHTML = "Va rugam asteptati raspunsul serverului...";
        swal({
            title: 'Se incarca!',
            content: info_text,
            icon: 'info',
            buttons: [false, false]
        });
        $.ajax({
            url: api_call,
            success: function (data) {
                $('#smartbill_issue').removeClass('disabled');
                $('#smrt_loading').remove();
                if (IsJsonString(data)) {
                    data = JSON.parse(data);
                } else {
                    var new_data = {};
                    new_data.message = data;
                    new_data.error = 'Not JSON';
                    data = new_data;
                }
                if (typeof data.error !== 'undefined' && !data.status) {
                    if (data.message == '') {
                        data.message = 'Verificati setarile modulului.';
                    }
                    if (data.message == '408') {
                        data.message = "A aparut o eroare la contactarea serverului SmartBill.Acceseaza cloud.smartbill.com, anuleaza factura pentru comanda #" + data.number + " si incearca din nou facturarea comenzii din magazinul online.";
                    }

                    Toastify({
                        text: "Eroare! " + data.message.replace("Smart Bill", "SmartBill").replace("SmartBill Cloud", "SmartBill"), duration: -1, newWindow: false, close: true, gravity: "top", position: 'center', backgroundColor: "#EF4136", stopOnFocus: true,
                    }).showToast();

                } else {
                    action = "emis";
                    if (!$('a.smrt-doc').length) {
                        var smartbill_mail_link = document.createElement('a');
                        smartbill_mail_link.href = $('#smartbill_issue')[0].href.replace('/smartbill_document', "/smartbill_document/send_mail");
                        smartbill_mail_link.className = "btn pull-right btn-info";
                        smartbill_mail_link.innerHTML = 'Trimite factura clientului';
                        smartbill_mail_link.id = "smartbill_mail";
                        $('#smartbill_issue').after(smartbill_mail_link);
                        $('#smartbill_mail').on('click', function (e) {
                            e.preventDefault();
                            var api_call = $(this).attr('href');
                            call_mail_doc(api_call);
                        });
                        var smartbill_link = document.createElement('a');
                        smartbill_link.href = data.documentUrl;
                        smartbill_link.target = "_blank";
                        smartbill_link.className = "btn pull-right smrt-doc";
                        smartbill_link.innerHTML = 'Deschide document';
                        $('#smartbill_issue').after(smartbill_link);
                    } else {
                        action = "remis";
                        $('a.smrt-doc').attr('href', data.documentUrl).show();
                    }
                    $('#smartbill_issue').addClass('reissue').html('Remite document');

                    Toastify({
                        text: "Documentul a fost " + action + " cu succes: " + data.series + " " + data.number + ".", duration: 3000, newWindow: false, close: true, gravity: "top", position: 'center', backgroundColor: "#00A14B", stopOnFocus: true,
                    }).showToast();
                }
                swal.close();
            },
            error: function (data) {
                $('#smartbill_issue').removeClass('disabled');
                $('#smrt_loading').remove();
                $('a.smrt-doc').show();

                Toastify({
                    text: "Eroare! " + data.responseText.replace("Smart Bill", "SmartBill").replace("SmartBill Cloud", "SmartBill"), duration: -1, newWindow: false, close: true, gravity: "top", position: 'center', backgroundColor: "#EF4136", stopOnFocus: true,
                }).showToast();
                swal.close();

            }
        });
    }

    // if in settings
    if ($('select[name="smartbill_document_type"]').length) {
        $('select[name="smartbill_document_type"]').on('change', function (e) {
            var $form = $(this).closest('.panel-body');
            switch ($(this).val()) {
                case '1':
                    $form.find('.series-estimate').show();
                    $form.find('.series-invoice').hide();
                    break;

                default:
                    $form.find('.series-estimate').hide();
                    $form.find('.series-invoice').show();
                    break;
            }
        }).trigger('change');
    }

    // if in order list
    if (typeof smartbill_documents != 'undefined') {
        var show_column = false;
        var smart_docs = [];
        smartbill_documents.forEach(function (i) {
            if (i['smartbill_document_url']) {
                show_column = true;
                smart_docs[i['order_id']] = i['smartbill_document_url']
            }
        });
        if (show_column) {
            $('#form-order table thead > tr:first-child').append('<td class="text-right">SmartBill</td>');
            $('#form-order table tbody > tr').each(function (ind) {
                var order_id = $(this).find('td:nth-child(2)').text();
                var row = '<td class="text-right"></td>';
                if (typeof smart_docs[order_id] != 'undefined') {
                    row = '<td class="text-right"><a target="_blank" href="' + smart_docs[order_id] + '" class="btn btn-primary" title="Vezi document SmartBill"><i class="fa fa-file-text"></i></a></td>';
                }
                $(this).append(row);
            });
        }
    }
});
