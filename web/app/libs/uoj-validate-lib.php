<?php

function validateLuoguProblemId($str) {
	return preg_match('/^(P|B)[1-9][0-9]{3,5}$/', $str);
}
