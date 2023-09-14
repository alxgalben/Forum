$(document).ready(function() {
    $(".btn-reply").click(function (e) {
        e.preventDefault();

        var comment = $("#reply_body").val();

        var data = {
            comment: comment
        };
        $.ajax({
            url: '/post-comment' + id,
            method: 'POST',
            data: data,
            success: function (response) {
                if (response.success) {
                    var newComment = '<div class="comment">' + comment + '</div>';
                    $(".comments").append(newComment);
                    //console.log("test");
                } else {
                    console.error(response.message);
                }
            },
            error: function (error) {
                console.error(error);
            }
        });
    });
});

