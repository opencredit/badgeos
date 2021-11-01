jQuery(document).ready(function ($) {
  $(".cmb2-id--ranks-unlock-with-points input[type=radio]").on(
    "click",
    function () {
      show_hide_unlock_points();
    }
  );

  function show_hide_unlock_points() {
    var is_unlock = $(
      ".cmb2-id--ranks-unlock-with-points input[type=radio]:checked"
    ).val();

    if (is_unlock == "Yes") {
      $(".cmb2-id--ranks-points-to-unlock").css("display", "block");
    } else {
      $(".cmb2-id--ranks-points-to-unlock").css("display", "none");
    }
  }

  /**
   * Make our Triggers list sortable
   */
  $("#ranks_steps_list").sortable({
    /**
     * When the list order is updated
     */
    update: function () {
      /**
       * Loop through each element
       */
      $("#ranks_steps_list li").each(function (index, value) {
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
  $("#ranks_steps_list").on("change", ".select-trigger-type", function () {
    /**
     * Grab our selected trigger type and achievement selector
     */
    var trigger_type = $(this).val();
    var achievement_selector = $(this).siblings(".select-achievement-post");
    var visit_post_selector = $(this).siblings(".badgeos-select-visit-post");
    var visit_page_selector = $(this).siblings(".badgeos-select-visit-page");
    var num_of_years = $(this).siblings(".badgeos-num-of-years");
    var num_of_months = $(this).siblings(".badgeos-num-of-months");
    var num_of_days = $(this).siblings(".badgeos-num-of-days");
    /**
     * If we're working with achievements, show the achievement selecter (otherwise, hide it)
     */
    if ("badgeos_specific_new_comment" == trigger_type) {
      achievement_selector.show();
    } else {
      achievement_selector.hide();
    }

    if ("badgeos_on_completing_num_of_year" == trigger_type) {
      num_of_years.show();
    } else {
      num_of_years.hide();
    }

    if ("badgeos_on_completing_num_of_month" == trigger_type) {
      num_of_months.show();
    } else {
      num_of_months.hide();
    }

    if ("badgeos_on_completing_num_of_day" == trigger_type) {
      num_of_days.show();
    } else {
      num_of_days.hide();
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

    $(".badgeos_achievements_step_fields").hide();
    $(".badgeos_achievements_step_ddl_dynamic").hide();
    $("#badgeos_achievements_step_ddl_dynamic_" + trigger_type)
      .show()
      .trigger("change");

    /**
     * Trigger a change for our achievement type post selector to determine if it should show
     */
    achievement_selector.change();
  });

  $(".badgeos_achievements_step_ddl_dynamic").on("change", function () {
    $(".badgeos_achievements_step_subddl_dynamic").hide();
    $(".badgeos_achievements_step_subtxt_dynamic").hide();
    main_trigger = $(this).data("trigger");
    curr_trigger = $(this).val();

    $(".badgeos_achievements_step_subddl_" + curr_trigger).show();
    $(".badgeos_achievements_step_subtxt_" + curr_trigger).show();
  });

  /**
   * Trigger a change for our trigger type post selector to determine if it should show
   */
  $(".select-trigger-type").change();
  show_hide_unlock_points();
});

/**
 * Add a step
 */
function badgeos_add_new_rank_req_step(achievement_id) {
  jQuery.post(
    ajaxurl,
    {
      action: "add_rank_req_step",
      achievement_id: achievement_id
    },
    function (response) {
      jQuery(response).appendTo("#ranks_steps_list");

      /**
       * Dynamically add the menu order for the new step to be one higher
       * than the last in line
       */
      new_step_menu_order =
        Number(
          jQuery("#ranks_steps_list li.step-row")
            .eq(-2)
            .children('input[name="order"]')
            .val()
        ) + 1;
      jQuery("#ranks_steps_list li.step-row:last")
        .children('input[name="order"]')
        .val(new_step_menu_order);

      /**
       * Trigger a change for the new trigger type <select> element
       */
      jQuery("#ranks_steps_list li.step-row:last")
        .children(".select-trigger-type")
        .change();
    }
  );
}

/**
 * Delete a step
 */
function badgeos_delete_rank_req_step(step_id) {
  jQuery.post(
    ajaxurl,
    {
      action: "delete_rank_req_step",
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
function badgeos_update_rank_steps(e) {
  jQuery(".save-ranks-steps-spinner").show();
  step_data = {
    action: "update_ranks_req_steps",
    steps: []
  };
  var total_steps = 0;
  /**
   * Loop through each step and collect its data
   */
  jQuery("#ranks_steps_list .step-row").each(function () {
    /**
     * Cache our step object
     */
    var step = jQuery(this);
    var trigger_type = step.find(".select-trigger-type").val();
    var visit_post_selector = step.find(".badgeos-select-visit-post").val();
    var visit_page_selector = step.find(".badgeos-select-visit-page").val();

    var selected_subtrigger = step
      .find(
        "#badgeos_achievements_step_dynamic_section_" +
        trigger_type +
        " .badgeos_achievements_step_ddl_dynamic"
      )
      .val();

    var selected_subtrigger_id = step
      .find(
        "#badgeos_achievements_step_dynamic_section_" +
        trigger_type +
        " .badgeos_achievements_step_ddl_dynamic"
      )
      .attr("id");
    var serialize_data = step
      .find(
        ".badgeos_achievements_step_fields_" + selected_subtrigger + ":input"
      )
      .serialize();

    /**
     * Setup our step object
     */
    var step_details = {
      step_id: step.attr("data-step-id"),
      order: step.find('input[name="order"]').val(),
      required_count: step.find(".required-count").val(),
      trigger_type: trigger_type,
      badgeos_subtrigger_id: selected_subtrigger_id,
      badgeos_subtrigger_value: selected_subtrigger,
      visit_post: visit_post_selector,
      visit_page: visit_page_selector,
      badgeos_fields_data: serialize_data,
      num_of_years: step.find(".badgeos-num-of-years").val(),
      num_of_months: step.find(".badgeos-num-of-months").val(),
      num_of_days: step.find(".badgeos-num-of-days").val(),
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
    jQuery(".save-ranks-steps-spinner").hide();
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
    jQuery(".save-ranks-steps-spinner").hide();
  });
}
