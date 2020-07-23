<?php
echo acym_round_chart('', $data['listStats']['deliveryRate'], 'delivery', 'cell large-auto medium-6 small-12', '<label>'.acym_translation('ACYM_DELIVERY_RATE').'</label>');
echo acym_round_chart('', $data['listStats']['openRate'], 'open', 'cell large-auto medium-6 small-12', '<label>'.acym_translation('ACYM_OPEN_RATE').'</label>');
echo acym_round_chart('', $data['listStats']['clickRate'], 'click', 'cell large-auto medium-6 small-12', '<label>'.acym_translation('ACYM_CLICK_RATE').'</label>');
echo acym_round_chart('', $data['listStats']['failRate'], 'fail', 'cell large-auto medium-6 small-12', '<label>'.acym_translation('ACYM_FAIL_RATE').'</label>');
echo acym_round_chart('', $data['listStats']['bounceRate'], '', 'cell large-auto medium-6 small-12', '<label>'.acym_translation('ACYM_BOUNCE_RATE').'</label>');
