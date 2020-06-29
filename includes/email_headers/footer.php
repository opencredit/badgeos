                    <?php
                      $badgeos_admin_tools = ( $exists = get_option( 'badgeos_admin_tools' ) ) ? $exists : array();
                    ?>
                   <br>
                  
                </td>
              </tr>

            <!-- END MAIN CONTENT AREA -->
            </table>

            <!-- START FOOTER -->
            <div class="footer" style="clear: both; Margin-top: 10px; text-align: center; width: 100%;color: #777;">
              <?php echo $badgeos_admin_tools['email_general_footer_text'];?>
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