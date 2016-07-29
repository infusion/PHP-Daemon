<?php

final class Worker {

	public static function run($mysqli) {
		
		$res = mysqli_query($mysqli, 'SELECT * FROM information_schema.tables LIMIT  ' . MAX_RESULT);
		
		$count = 0;
		
		for ($count = 0; null !== ($row = mysqli_fetch_assoc($res)); $count++) {
			
			// Do something			
		}
		mysqli_free($res);

		echos("Blub?");
		
		return $count / MAX_RESULT;
	}
}
