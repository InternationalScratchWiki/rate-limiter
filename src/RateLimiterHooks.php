<?php
class RateLimiterHooks {
	public static function onEdit(EditPage $editor, string $text, string $section, string &$error, string $summary) {
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
	
	public static function onTitleMove(Title $oldTitle, Title $newTitle, User $user, string $reason, Status &$status) {
		if (LimitValidator::isLimited($user, $restriction)) {
			$status->fatal('rate-limiter-rate-limited', $restriction['limit'], $restriction['interval']);
		}
	}
}