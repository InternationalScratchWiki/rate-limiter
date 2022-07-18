# Rate Limiter
Limit the rate of edits and moves for users

## Config
- `$wgRateLimiterRestrictions` - the restrictions applied to each user group, in the following format:
	```
	[
		'limited' => true, // set to true to apply a limit, false to explicitly remove any limits
		'limit' => 1, // the amount of edits and moves allowed in the interval
		'interval' => 600, // the interval (in seconds)
		'priority' => 1 // the priority (higher priority takes precedence over lower priority if the user is in multiple groups with different restrictions)
	]
	```