jQuery(document).ready(function ($) {
  /**
   * Hide our Triggers metabox if unnecessary
   */
  $("#_badgeos_earned_by")
    .change(function () {
      if ("triggers" == $(this).val()) {
        $("#badgeos_steps_ui").show();
      } else {
        $("#badgeos_steps_ui").hide();
      }
    })
    .change();

  /**
   * Make our Triggers list sortable
   */
  $("#award_steps_list").sortable({
    /**
     * When the list order is updated
     */
    update: function () {
      /**
       * Loop through each element
       */
      $("#award_steps_list li").each(function (index, value) {
        /**
         * Write it's current position to our hidden input value
         */
        $(this)
          .children('input[name="order"]')
          .val(index);
      });
    }
  });

  /**
   * Listen for our change to our trigger type selectors
   */
  $("#award_steps_list").on("change", ".select-trigger-type", function () {
    /**
     * Grab our selected trigger type and achievement selector
     */
    var trigger_type = $(this).val();
    var achievement_selector = $(this).siblings(".select-achievement-type");
    var visit_post_selector = $(this).siblings(".badgeos-select-visit-post");
    var visit_page_selector = $(this).siblings(".badgeos-select-visit-page");
    var num_of_years = $(this).siblings(".badgeos-num-of-years");
    var num_of_days = $(this).siblings(".badgeos-num-of-days");
    var num_of_days_login = $(this).siblings(".badgeos-num-of-days-login");
    /**
     * If we're working with awardpoint, show the achievement selecter (otherwise, hide it)
     */
    if (
      "any-achievement" == trigger_type ||
      "all-achievements" == trigger_type ||
      "specific-achievement" == trigger_type
    ) {
      achievement_selector.show();
    } else {
      achievement_selector.hide();
    }

    if ("badgeos_visit_a_page" == trigger_type || "badgeos_award_author_on_visit_page" == trigger_type) {
      visit_page_selector.show();
    } else {
      visit_page_selector.hide();
    }

    if ("badgeos_visit_a_post" == trigger_type || "badgeos_award_author_on_visit_post" == trigger_type) {
      visit_post_selector.show();
    } else {
      visit_post_selector.hide();
    }

    if ("badgeos_on_completing_num_of_year" == trigger_type) {
      num_of_years.show();
    } else {
      num_of_years.hide();
    }

    if ("badgeos_wp_login" == trigger_type) {
      num_of_days.show();
    } else {
      num_of_days.hide();
    }
    
    if ("badgeos_wp_login_x_days" == trigger_type) {
      num_of_days_login.show();
    } else {
      num_of_days_login.hide();
    }


    $(".badgeos_awardpoint_step_fields").hide();
    $(".badgeos_awardpoint_step_ddl_dynamic").hide();
    $("#badgeos_awardpoint_step_ddl_dynamic_" + trigger_type)
      .show()
      .trigger("change");

    /**
     * Trigger a change for our achievement type post selector to determine if it should show
     */
    achievement_selector.change();
  });

  $(".badgeos_awardpoint_step_ddl_dynamic").on("change", function () {
    $(".badgeos_awardpoint_step_subddl_dynamic").hide();
    $(".badgeos_awardpoint_step_subtxt_dynamic").hide();
    main_trigger = $(this).data("trigger");
    curr_trigger = $(this).val();

    $(".badgeos_awardpoint_step_subddl_" + curr_trigger).show();
    $(".badgeos_awardpoint_step_subtxt_" + curr_trigger).show();
  });

  /**
   * Trigger a change for our trigger type post selector to determine if it should show
   */
  $(".select-trigger-type").change();

  /**
   * Listen for a change to our achievement type selectors
   */
  $("#award_steps_list").on("change", ".select-achievement-type", function () {
    /**
     * Setup our necessary variables
     */
    var achievement_selector = $(this);
    var achievement_type = achievement_selector.val();
    var step_id = achievement_selector.parent("li").attr("data-step-id");
    var excluded_posts = [
      achievement_selector.siblings('input[name="post_id"]').val()
    ];
    var trigger_type = achievement_selector
      .siblings(".select-trigger-type")
      .val();

    /**
     * If we've selected a *specific* achievement type, show our post selector
     * and populate it w/ the corresponding achievement posts
     */
    if ("" !== achievement_type && "specific-achievement" == trigger_type) {
      $.post(
        ajaxurl,
        {
          action: "post_award_select_ajax",
          achievement_type: achievement_type,
          step_id: step_id,
          excluded_posts: excluded_posts
        },
        function (response) {
          achievement_selector
            .siblings("select.select-achievement-post")
            .html(response);
          achievement_selector
            .siblings("select.select-achievement-post")
            .show();
        }
      );

      /**
       * Otherwise, keep our post selector hidden
       */
    } else {
      achievement_selector.siblings(".select-achievement-post").hide();
      if ("badgeos_specific_new_comment" == trigger_type) {
        achievement_selector.siblings("input.select-achievement-post").show();
      }
    }
  });

  /**
   * Trigger a change for our achievement type post selector to determine if it should show
   */
  $(".select-achievement-type").change();
});

/**
 * Add a step
 */
function badgeos_add_new_award_step(achievement_id) {
  jQuery.post(
    ajaxurl,
    {
      action: "add_award_step",
      achievement_id: achievement_id
    },
    function (response) {
      jQuery(response).appendTo("#award_steps_list");

      /**
       * Dynamically add the menu order for the new step to be one higher
       * than the last in line
       */
      new_step_menu_order =
        Number(
          jQuery("#award_steps_list li.step-row")
            .eq(-2)
            .children('input[name="order"]')
            .val()
        ) + 1;
      jQuery("#award_steps_list li.step-row:last")
        .children('input[name="order"]')
        .val(new_step_menu_order);

      /**
       * Trigger a change for the new trigger type <select> element
       */
      jQuery("#award_steps_list li.step-row:last")
        .children(".select-trigger-type")
        .change();
    }
  );
}

/**
 * Delete a step
 *
 * @param step_id
 */
function badgeos_delete_award_step(step_id) {
  jQuery.post(
    ajaxurl,
    {
      action: "delete_award_step",
      step_id: step_id
    },
    function (response) {
      jQuery(".step-" + step_id).remove();
    }
  );
}

/**
 * Update all steps
 */
function badgeos_update_award_steps(e) {
  jQuery(".save-award-steps-spinner").show();
  step_data = {
    action: "update_award_steps",
    steps: []
  };
  var total_steps = 0;
  /**
   * Loop through each step and collect its data
   */
  jQuery("#award_steps_list .step-row").each(function () {
    /**
     * Cache our step object
     */
    var step = jQuery(this);
    var trigger_type = step.find(".select-trigger-type").val();
    var visit_post_selector = step.find(".badgeos-select-visit-post").val();
    var visit_page_selector = step.find(".badgeos-select-visit-page").val();
    var selected_subtrigger = step
      .find(
        "#badgeos_awardpoint_step_dynamic_section_" +
        trigger_type +
        " .badgeos_awardpoint_step_ddl_dynamic"
      )
      .val();

    var selected_subtrigger_id = step
      .find(
        "#badgeos_awardpoint_step_dynamic_section_" +
        trigger_type +
        " .badgeos_awardpoint_step_ddl_dynamic"
      )
      .attr("id");
    var serialize_data = step
      .find(".badgeos_awardpoint_step_fields_" + selected_subtrigger + ":input")
      .serialize();

    /**
     * Setup our step object
     */
    var step_details = {
      step_id: step.attr("data-step-id"),
      order: step.find('input[name="order"]').val(),
      required_count: step.find(".required-count").val(),
      point_value: step.find(".point-value").val(),
      trigger_type: trigger_type,
      achievement_type: step.find(".select-achievement-type").val(),
      badgeos_subtrigger_id: selected_subtrigger_id,
      badgeos_subtrigger_value: selected_subtrigger,
      badgeos_fields_data: serialize_data,
      visit_post: visit_post_selector,
      num_of_years: step.find(".badgeos-num-of-years").val(),
      num_of_days: step.find(".badgeos-num-of-days").val(),
      num_of_days_login: step.find(".badgeos-num-of-days-login").val(),
      visit_page: visit_page_selector,
      achievement_post:
        "badgeos_specific_new_comment" === trigger_type
          ? step.find("input.select-achievement-post").val()
          : step.find("select.select-achievement-post").val(),
      title: step.find(".step-title .title").val()
    };

    /**
     * Allow external functions to add their own data to the array
     */
    step.trigger("update_step_data", [step_details, step]);

    /**
     * Add our relevant data to the array
     */
    step_data.steps.push(step_details);
    total_steps++;
  });

  if (total_steps == 0) {
    jQuery(".save-award-steps-spinner").hide();
  }

  jQuery.post(ajaxurl, step_data, function (response) {
    /**
     * Parse our response and update our step titles
     */
    var titles = jQuery.parseJSON(response);
    jQuery.each(titles, function (index, value) {
      jQuery("#step-" + index + "-title").val(value);
    });

    /**
     * Hide our save spinner
     */
    jQuery(".save-award-steps-spinner").hide();
  });
}
