<?php
use MediaWiki\Hook\TitleMoveHook;
use MediaWiki\Hook\EditFilterHook;

class RateLimiterHooks implements TitleMoveHook, EditFilterHook {
	public function onEditFilter($editor, $text, $section, &$error, $summary) {
		if (LimitValidator::isLimited($editor->getContext()->getUser(), $restriction)) {
			$error = Html::rawElement(
				'div', 
				['class' => 'errorbox'],
				Html::rawElement(
					'p',
					[],
					wfMessage('rate-limiter-rate-limited', $restriction['limit'], $restriction['interval'])->parse()
				)
			);
		}
		
		return true;
	}
	
	public function onTitleMove(Title $oldTitle, Title $newTitle, User $user, $reason, Status &$status) {
		if (LimitValidator::isLimited($user, $restriction)) {
			$status->fatal('rate-limiter-rate-limited', $restriction['limit'], $restriction['interval']);
		}
	}
}