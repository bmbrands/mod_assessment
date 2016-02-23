$(function(){

    $('.feedbacklink').click(function(e) {
        link = $(this);
        e.preventDefault();
        userrow = link.closest('.userrow');
        userid = userrow.attr('data-id');
        feedbackrow = userrow.find('#feedback');
        feedbackrow.toggleClass('hidden');
    });

    $('.award').click(function() {
        medal = $(this);
        awardid = medal.attr('id');

        userrow = medal.closest('.userrow');
        user = userrow.attr('data-id');
        assessment = userrow.attr('data-assessment');
        loader = userrow.find('.loadericon');
        loader.removeClass('hidden');
        checkicon = userrow.find('.checkicon');

        // Set the table row
        userrow.find('.award').each(function () {
            medal.removeClass('active');
        });
        medal.addClass('active');

        var setgrade = $.ajax({
            method: 'POST',
            url: M.cfg.wwwroot + '/mod/assessment/ajax/assessment_grade_ajax.php',
            data: { action: 'rating',
                     assessmentid: assessment,
                     sesskey: M.cfg.sesskey,
                     userid: user,
                     awardid: awardid
                    }
        });
        setgrade.done(function(results) {
            loader.addClass('hidden');
            if (results.stat === 'success') {
                checkicon.removeClass('hidden');
            }
            if (results.stat === 'unset') {
                medal.removeClass('active');
            }
        });
    });

    $('.commentadd').click(function() {
        btn = $(this);
        userrow = btn.closest('.userrow');
        user = userrow.attr('data-id');
        assessment = userrow.attr('data-assessment');
        commentinput = userrow.find('.commentinput');
        commenthtml = commentinput.html();
        userrow.find('.feedbackcontent').html(commenthtml);
        userrow.find('.commentinput').html(commenthtml);
        var setfeedback = $.ajax({
            method: 'POST',
            url: M.cfg.wwwroot + '/mod/assessment/ajax/assessment_feedback_ajax.php',
            data: { action: 'rating',
                     assessmentid: assessment,
                     sesskey: M.cfg.sesskey,
                     userid: user,
                     value: commenthtml
                    }
        });
        setfeedback.done(function(results) {
            console.log(results);
        });

    });

    $('.commentcancel').click(function() {
        btn = $(this);
        userrow = btn.closest('.userrow');
        link = userrow.find('.feedbacklink');
        feedbackrow = userrow.find('#feedback');
        feedbackrow.toggleClass('hidden');
    });
});