<?php
function safe($str)
{
	global $mysqli;
	if (get_magic_quotes_gpc()) {
		$str = stripslashes($str);
	}
	$str = $mysqli->real_escape_string($str);
	return "'" . $str . "'";
}

function insertInto($table, $data, $desc = '')
  {
    $query = '';
    foreach ($data as $key => $val) {
      $query .= ($query != '' ? ', ' : '') . '`' . $key . '` = ' . $this->safe($val);
    }
    $query = 'INSERT INTO ' . $table . ' SET ' . $query;
    return $execQuery($query, $desc);
  }
  
  
  
function rowCount($result)
{
	return mysqli_num_rows($result);
}

function fetchArray($result)
{
	if (is_bool($result)) {
		return Array();
	}    
	return mysqli_fetch_array($result);
}

function fetchAssoc($result)
{
	if (is_bool($result)) {
		return Array();
	}    
	return mysqli_fetch_assoc($result);
}

function fetchRow($result)
{
	if (is_bool($result)) {
		return Array();
	}

	return mysqli_fetch_row($result);
}

function execQuery($query, $desc = '')
{
	$this->lastQuery = $query;
	
	if ($result = @$mysqli->query($query)) {
		return $result;
	} else {
// 		$this->logDBError($query, mysqli_error($this->__db_link), $desc, 'dbExecQuery');
	}
}  
?>