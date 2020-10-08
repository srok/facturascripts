/*!
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

function round(number, decimals) {
    var aux = Math.pow(10, decimals);
    return Math.round(number * aux) / aux;
}

$(document).ready(function () {
    $(".btn-reset-rates").click(function () {
        $(this.form.action).val('reset-rates');
        $(this.form).submit();
    });
    $(".prices-tab-cost").change(function () {
        var coste = parseFloat($(this).val().replace(",", "."));
        $(this.form.coste).val(coste);

        var margen = parseFloat($(this.form.margen).val());
        if (margen > 0) {
            var decimals = parseFloat($(this.form.decimals).val());
            var iva = parseFloat($(this.form.iva).val());
            var precio = coste * (100 + margen) / 100;
            $(this.form.precio).val(precio);
            $(this.form.precioimp).attr("placeholder", round(precio * (100 + iva) / 100, decimals));
        }
    });
    $(".prices-tab-margin").change(function () {
        var margen = parseFloat($(this).val().replace(",", "."));
        $(this.form.margen).val(margen);

        if (margen > 0) {
            var coste = parseFloat($(this.form.coste).val());
            var decimals = parseFloat($(this.form.decimals).val());
            var iva = parseFloat($(this.form.iva).val());
            var precio = coste * (100 + margen) / 100;
            $(this.form.precio).val(precio);
            $(this.form.precioimp).attr("placeholder", round(precio * (100 + iva) / 100, decimals));
        }
    });
    $(".prices-tab-price").change(function () {
        var precio = parseFloat($(this).val().replace(",", "."));
        $(this.form.precio).val(precio);

        $(this.form.margen).val(0);
        var decimals = parseFloat($(this.form.decimals).val());
        var iva = parseFloat($(this.form.iva).val());
        $(this.form.precioimp).attr("placeholder", round(precio * (100 + iva) / 100, decimals));
    });
    $(".prices-tab-pricetax").change(function () {
        var precioimp = parseFloat($(this).val().replace(",", "."));
        $(this.form.precioimp).val(precioimp);

        $(this.form.margen).val(0);
        var iva = parseFloat($(this.form.iva).val());
        var precio = (100 * precioimp) / (100 + iva);
        $(this.form.precio).val(round(precio, 5));
    });
});