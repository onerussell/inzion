<?php /* Smarty version 2.6.11, created on 2014-03-15 08:51:13
         compiled from mods/_popups/_warn.html */ ?>
						<div id="id_warn_popup" class="aj-box01" align="center" style="display: none; position: fixed; z-index: 4444; max-height: 100px">
							<div class="aj-close"><a href="javascript: void(0);" onclick="javascript: $('#id_warn_popup').hide(); setTimeout('$(\'#id_eclipse__bckgrnd\').hide()', 200);"><img src="<?php echo $this->_tpl_vars['imgDir']; ?>
close_ico.gif"  /></a></div>
							
							<div class="" style="max-height: 120px; margin: 10px; border: none !important;">
								<div>
									<div>
										<fieldset style="border: none !important;">
											<div>
												<div>
													<b style="color: blue"><span id="id_warn_text">Some text...</span></b>
												</div>
											</div>
										</fieldset>
									</div>
								</div>
							</div>
							
							<div class="aj-button" align="left">
								<span class="aj-button02"><a class="warn_nav" id="id_warn_popup_mbtn_valbum" href="javascript: void(0);" onclick="javascript: window.location.reload( true ); /*$('#id_warn_popup').hide();$('#id_eclipse_bckgrnd').hide();*/">Ok</a></span>
								<span class="aj-button02"><a class="warn_nav" id="id_warn_popup_mbtn_album" href="javascript: void(0);" onclick="javascript: window.location.reload( true ); /*$('#id_warn_popup').hide();$('#id_eclipse_bckgrnd').hide();*/">Ok</a></span>
								<span class="aj-button02"><a class="warn_nav" id="id_warn_popup_mbtn_profile" href="javascript: void(0);" onclick="javascript: window.location.reload( true ); /*$('#id_warn_popup').hide();$('#id_eclipse_bckgrnd').hide();*/" style="display: none">Ok</a></span>
								<span class="aj-button02"><a class="warn_nav" id="id_warn_popup_mbtn_ward" href="javascript: void(0);" onclick="javascript: window.location.reload( true ); /*$('#id_warn_popup').hide();$('#id_eclipse_bckgrnd').hide();*/" style="display: none">Ok</a></span>
								<span class="aj-button02"><a class="warn_nav" id="id_warn_popup_mbtn_mission" href="javascript: void(0);" onclick="javascript: window.location.reload( true ); /*$('#id_warn_popup').hide();$('#id_eclipse_bckgrnd').hide();*/" style="display: none">Ok</a></span>
							</div>
							<span class="block-bottom">&nbsp;</span>
						</div>