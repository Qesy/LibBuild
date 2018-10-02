<?
const DB_NAME = 'marohn'; 
const CLASS_PRE = 'mar';
define('LIB_DIR', 'CLASS_LIB_'.DB_NAME);

function build($Table, $dbh){
	$ClassName = strtoupper(CLASS_PRE).'_'.ucfirst($Table);
	$result = $dbh->query('SHOW FULL COLUMNS FROM '.CLASS_PRE.'_'.$Table);
	$arr = $result->fetchAll ( PDO::FETCH_ASSOC );
	$PrimaryKey = '';
	$Date = date('Ymd');
	$Field = $Assignment = $AddValue = '';
	foreach($arr as $k => $v){
		if($v['Key'] == 'PRI') $PrimaryKey = $v['Field'];
		$Field .= 'public $'.$v['Field'].";\n	";
		$Assignment .= '$this->'.$v['Field'].' = $rs[\''.$v['Field']."'];\n		";
		if($v['Key'] != 'PRI'){				
			$AddValue .=  '\''.$v['Field'].'\' => $this->'.$v['Field'].",\n		";
		}
	}		
	$tmp = file_get_contents('lib.temp');
	$str = str_replace(array('{Date}', '{Table}', '{PrimaryKey}', '{Field}', '{Assignment}', '{AddValue}' ,'{ClassName}'), array($Date, $Table, $PrimaryKey, $Field, $Assignment, $AddValue, $ClassName), $tmp);
	file_put_contents(LIB_DIR.'/'.$ClassName.'.php', $str);
}
if(!empty($_POST)){
	header("Content-type: text/html; charset=gbk");
	try {
	    $dbh = new PDO('mysql:host=localhost;dbname='.DB_NAME, 'root', 'root');
	    $dbh->exec( "SET NAMES utf-8");
	} catch (PDOException $e) {
	    print "Error!: " . $e->getMessage() . "<br/>";
	    die();
	}
	$ret = '';
	switch($_POST['Method']){
		case 'Tables';
			$result = $dbh->query('show tables;');
			$arr = $result->fetchAll ( PDO::FETCH_ASSOC );
			$ret = json_encode($arr);
			break;
		case 'Build':		
			if(!is_dir(LIB_DIR)) mkdir ( LIB_DIR, 0777 );
			if($_POST['Table'] != 'All'){
				$_POST['Table'] = substr($_POST['Table'], strlen(CLASS_PRE.'_'));
				build($_POST['Table'], $dbh);	
			}else{
				$result = $dbh->query('show tables;');
				$arr = $result->fetchAll ( PDO::FETCH_ASSOC );
				foreach($arr as $k => $v){
					$Table = substr($v['Tables_in_'.DB_NAME], strlen(CLASS_PRE.'_'));
					build($Table, $dbh);
				}
			}		
			break;
	}
	echo $ret;
	exit;
}


?>
<!DOCTYPE html>
<html>
<head>
	<title>类库生成器</title>
	<meta charset="UTF-8">	
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.css" rel="stylesheet">
	<style type="text/css">
		a:link{
			padding: 0px 10px 0px 10px;
		}
	</style>
</head>
<body style="padding-top: 100px;">
	<div class="container" style="width: 300px;">
	<h1>类库生成器</h1>
	<form>
		<div class="form-group">
		    <label for="exampleInputEmail1">数据库名：</label>
		    <input class="form-control" id="dbname" value="<?=DB_NAME?>">
		</div>
		<div class="form-group">
		    <label for="exampleInputEmail1">类库前缀：</label>
		    <input class="form-control" id="classPre" value="<?=CLASS_PRE?>">
		</div>
		<div class="form-group">
		    <label for="exampleInputEmail1">选择生成表：</label>
		    <select id="table" class="form-control">
				<option value="All">全部表</option>
			</select>
		</div>	
		<button type="button" id="build" class="btn btn-success btn-block">生成</button>
	</form>
	</div>
<script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>
<script type="text/javascript">
	$(function(){
		$.post('build.php', {'Method' : 'Tables'}, function(data){
			$.each(data, function(k, v){
				$('#table').append('<option>'+v["Tables_in_<?=DB_NAME?>"]+'</option>');
			})
		}, 'json')
		$('#build').click(function(){
			$.post('build.php', {'Method' : 'Build', 'Table' : $('#table').val()}, function(data){
				//console.log(data)
				alert('生成完成');
			})
		})
	})
</script>
</body>
</html>