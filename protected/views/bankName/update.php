<?php
$this->breadcrumbs=array(
	'Bank Names'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List BankName','url'=>array('index')),
	array('label'=>'Create BankName','url'=>array('create')),
	array('label'=>'View BankName','url'=>array('view','id'=>$model->id)),
	array('label'=>'Manage BankName','url'=>array('admin')),
);
?>

<h1>Update BankName <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form',array('model'=>$model)); ?>