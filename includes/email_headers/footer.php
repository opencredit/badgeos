<?php
  $badgeos_admin_tools                  = ( $exists = get_option( 'badgeos_admin_tools' ) ) ? $exists : array();
  $email_general_footer_background_color= !empty( $badgeos_admin_tools['email_general_footer_background_color'] )? $badgeos_admin_tools['email_general_footer_background_color'] : '#ffffff';
  $email_general_footer_text_color        = !empty( $badgeos_admin_tools['email_general_footer_text_color'] )? $badgeos_admin_tools['email_general_footer_text_color'] : '#000000';
?>                    
                    <?php
                      $badgeos_admin_tools = ( $exists = get_option( 'badgeos_admin_tools' ) ) ? $exists : array();
                    ?>
                   <br>
                  
                </td>
              </tr>

            <!-- END MAIN CONTENT AREA -->
            </table>

            <!-- START FOOTER -->
            <div class="footer" style="background-color:<?php echo $email_general_footer_background_color;?>;clear: both; text-align: center; width: 100%;color: <?php echo $email_general_footer_text_color;?>;">
              <div style="padding:10px"><?php echo $badgeos_admin_tools['email_general_footer_text'];?></div>
            </div>
            <!-- END FOOTER -->

          <!-- END CENTERED WHITE CONTAINER -->
          </div>
        </td>
        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
      </tr>
    </table>
  </body>
</html>