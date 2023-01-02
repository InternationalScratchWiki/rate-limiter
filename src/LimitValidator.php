<?php
use MediaWiki\MediaWikiServices;

class LimitValidator {
	public static function isLimited(User $user, &$out_restriction) : bool {
		$restriction = self::getLimitForUserGroups(self::getUserGroups($user));
		if ($restriction == null || !$restriction['limited']) { // if there isn't a limit for this user, just allow this and skip the query
			return false;
		}
		
		$out_restriction = $restriction;
		
		$numRecentActions = self::numberOfRecentActionsByUser($user, $restriction['interval']);
		
		return $numRecentActions >= $restriction['limit'];
	}
	
	private static function getUserGroups(User $user) : array {
		$userGroupManager = MediaWiki\MediaWikiServices::getInstance()->getUserGroupManager();
		return $userGroupManager->getUserEffectiveGroups($user);
	}
	
	private static function numberOfRecentActionsByUser(User $user, int $interval) : ?int {
		$loadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $loadBalancer->getConnection( DB_REPLICA );
		
		$timestampFloor = $dbr->timestamp(wfTimestamp(TS_UNIX) - $interval);
		
		// TODO: consolidate these into a single query for improved performance
		$revisions = $dbr->selectRow(
			['revision'], //tables
			['count' => 'COUNT(1)'], //fields
			['rev_actor' => $user->getActorId(), 'rev_timestamp > ' . $timestampFloor], //conditions
			__METHOD__,
			[], // options
			[]
		);
		$tempRevisions = $dbr->selectRow(
			['revision_actor_temp'],
			['count' => 'COUNT(1)'],
			['revactor_actor' => $user->getActorId(), 'revactor_timestamp > ' . $timestampFloor],
			__METHOD__,
			[],
			[]
		);
				
		return $revisions->count + $tempRevisions->count;
	}
	
	private static function getLimitForUserGroups(array $userGroups) {
		global $wgRateLimiterRestrictions;
		
		$applicableRestrictions = array_filter($wgRateLimiterRestrictions, function($restriction, $group) use ($userGroups) {
			return in_array($group, $userGroups);
		}, ARRAY_FILTER_USE_BOTH);
		
		if (empty($applicableRestrictions)) {
			return null;
		}
		
		return array_reduce($applicableRestrictions, function ($incumbent, $challenger) {
			return self::mergeRestrictions($incumbent, $challenger);
		});
	}
	
	private static function mergeRestrictions($first, $second) {
		if (!$first) {
			return $second;
		} else {
			return $first['priority'] > $second['priority'] ? $first : $second;
		}
	}
}