$("#contactForm").on("submit", function(event) {
    event.preventDefault();
    let token = $("meta[name='csrf-token']").attr("content");
    let url = "/contactRequest";
    let name = $("#nameContact").val();
    let phone = $("#phoneContact").val();
    let comments = $("#commentsContact").val();
    $.ajax({
        type: "POST",
        url: url,
        data: {
            _token: token,
            name: name,
            phone: phone,
            comments: comments
        },
        success: function(result) {
            console.log(result);
        },
        error: function(error) {
            console.log(error);
        }
    });
});
