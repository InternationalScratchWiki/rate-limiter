<?php
class LimitValidator {
	public static function isLimited(User $user, &$out_restriction) : bool {
		$restriction = self::getLimitForUserGroups($user->getGroups());
		if ($restriction == null) { // if there isn't a limit for this user, just allow this and skip the query
			return false;
		}
		
		$out_restriction = $restriction;
		
		$numRecentActions = self::numberOfRecentActionsByUser($user, $restriction['interval']);
		
		return $numRecentActions > $restriction['limit'];
	}
	
	private static function numberOfRecentActionsByUser(User $user, int $interval) {
		$dbr = wfGetDb( DB_REPLICA );
		
		$row = $dbr->selectRow(
			['recentchanges'], //tables
			['count' => 'COUNT(1)'], //fields
			['rc_actor' => $user->getActorId(), 'rc_timestamp > ' . $dbr->timestamp(wfTimestamp(TS_UNIX) - $interval)], //conditions
			__METHOD__,
			['ORDER BY' => 'rc_timestamp DESC'], // options
			[]
		);
				
		return $row->count;
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