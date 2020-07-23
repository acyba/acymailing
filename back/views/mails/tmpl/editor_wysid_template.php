<?php if (strpos($this->content, 'acym__wysid__template') !== false) {
    echo $this->content;
} else { ?>
	<div id="acym__wysid__template" class="cell acym__foundation__for__email">
		<table class="body">
			<tbody>
				<tr>
					<td align="center" class="center acym__wysid__template__content" valign="top" style="background-color: rgb(120, 120, 120); padding: 30px 0 120px 0;">
						<center>
							<table align="center" border="0" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
										<td class="acym__wysid__row ui-droppable ui-sortable" bgcolor="#ffffff" style="background-color: rgb(255, 255, 255);">
											<table class="row acym__wysid__row__element" border="0" cellpadding="0" cellspacing="0">
												<tbody>
													<tr>
														<th class="small-12 medium-12 large-12 columns">
															<table class="acym__wysid__column acym__wysid__column__first" style="min-height: 75px; display: block;" border="0" cellpadding="0" cellspacing="0">
																<tbody class="ui-sortable" style="min-height: 75px; display: block;">
                                                                    <?php
                                                                    if (!empty($this->content)) {
                                                                        echo '<tr class="acym__wysid__column__element ui-draggable" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">
																							<td class="large-12 acym__wysid__column__element__td" style="outline: rgb(0, 163, 254) dashed 0px; outline-offset: -1px;">
																								<div class="acym__wysid__tinymce--text mce-content-body" id="mce_0" contenteditable="true" style="position: relative;" spellcheck="false">
																									'.acym_absoluteURL($this->content).'
																								</div>
																							</td>
																						</tr>';
                                                                    }
                                                                    ?>
																</tbody>
															</table>
														</th>
													</tr>
												</tbody>
											</table>
										</td>
									</tr>
								</tbody>
							</table>
						</center>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
<?php } ?>