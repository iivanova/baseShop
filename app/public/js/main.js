$(document).ready(function ()
{
    function validateForm() {
        let x = document.forms["myForm"]["fname"].value;
        if (x == "") {
            alert("Name must be filled out");
            return false;
        }
    }

    var updateTotal = function (data) {

//        console.log(data);
//        $("#cart-total").html(data);
        location.reload();

    }
    var addUrl = '/cart/add_product/';
    $('.add-product').on('click', function (event) {
        let data = {'productId': this.getAttribute('data-value')};
        $.ajax({
            type: "POST",
            url: addUrl,
            data: data,
            success: updateTotal
        });
    })

    var removeUrl = '/cart/remove_product/';
    $('.remove-product').on('click', function (event) {
        let data = {'productId': this.getAttribute('data-value')};
        $.ajax({
            type: "POST",
            url: removeUrl,
            data: data,
            success: updateTotal
        });
    })


});