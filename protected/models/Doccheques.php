<?php

/* * *********************************************************************************
 * The contents of this file are subject to the Mozilla Public License Version 2.0
 * ("License"); You may not use this file except in compliance with the Mozilla Public License Version 2.0
 * The Original Code is:  Linet 3.0 Open Source
 * The Initial Developer of the Original Code is Adam Ben Hur.
 * All portions are Copyright (C) Adam Ben Hur.
 * All Rights Reserved.
 * ********************************************************************************** */

/**
 * This is the model class for table "cheques".
 *
 * The followings are the available columns in table 'cheques':
 * @property string $prefix
 * @property string $refnum
 * @property integer $type
 * @property integer $creditcompany
 * @property string $cheque_num
 * @property string $bank
 * @property string $branch
 * @property string $cheque_acct
 * @property string $cheque_date
 * @property string $sum
 * @property string $bank_refnum
 * @property string $dep_date
 * @property integer $id
 */
class Doccheques extends basicRecord {

    const table = '{{docCheques}}';

    //private $dateDBformat = true;

    /*
     * for open format export 
     */

    public function getType() {
        return isset($this->Doc) ? $this->Doc->getType() : "";
    }

    public function getNum() {
        return isset($this->Doc) ? $this->Doc->docnum : "";
    }

    public function getDate() {
        return isset($this->Doc) ? $this->Doc->issue_date : "";
    }

    public function openfrmt($line) {
        $rcps = '';

        //get all fields (D110) sort by id
        $criteria = new CDbCriteria;
        $criteria->condition = "type_id = :type_id";
        $criteria->params = array(':type_id' => "D120");
        $fields = OpenFormat::model()->findAll($criteria);

        //loop strfgy
        foreach ($fields as $field) {
            $rcps.=$this->openfrmtFieldStr($field, $line);
        }
        return $rcps . "\r\n";
    }

    public function transaction($transaction, $action, $account_id) {
        $model = PaymentType::model()->findByPk($this->type);
        $paymenet = new $model->value;

        $in = new Transactions();
        $in->num = $transaction->num;
        $in->account_id = $account_id;
        $in->type = $transaction->type;
        $in->refnum1 = $transaction->refnum1;
        $in->valuedate = $transaction->valuedate;
        $in->details = $transaction->details;

        $in->currency_id = $this->currency_id;
        $in->sum = $this->sum * $action;
        $in->owner_id = Yii::app()->user->id;
        $in->linenum = $transaction->linenum;

        $transaction->linenum++;

        $out = new Transactions();

        //if($this->Type->oppt_account_id!=0)
        $out->account_id = $this->Type->oppt_account_id;
        $out->type = $transaction->type;
        $out->refnum1 = $transaction->refnum1;
        $out->valuedate = $transaction->valuedate;
        $out->details = $transaction->details;

        $out->currency_id = $this->currency_id;
        $out->sum = $this->sum * $action * -1;
        $out->owner_id = Yii::app()->user->id;
        $out->linenum = $transaction->linenum;

        $transaction->linenum++;



        if (method_exists($paymenet, "transaction")) {

            $paymenet->transaction($in, $out, $this);
        } else {
            
        }

        $num = $in->save();
        $out->num = $num;
        $num = $out->save();
        return $transaction;
    }

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Cheques the static model class
     */
    public function primaryKey() {
        return array('doc_id', 'line');
    }

//*/

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return self::table;
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        return array(
            array('type, doc_id, line', 'numerical', 'integerOnly' => true),
            array('sum', 'length', 'min' => 1),
            //array('doc_id', 'length', 'max' => 10),
            array('currency_id', 'length', 'max' => 3),
            //array('cheque_acct, cheque_num, bank_refnum', 'length', 'max' => 20),
            array('sum', 'length', 'max' => 8),
            array('currency_id', 'safe'),
            array('currency_id, doc_id, type, sum, line, bank_refnum', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'Doc' => array(self::BELONGS_TO, 'Docs', 'doc_id'),
            'Type' => array(self::BELONGS_TO, 'PaymentType', 'type'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'doc_id' => Yii::t('labels', 'Refnum'),
            'type' => Yii::t('labels', 'Type'),
            'sum' => Yii::t('labels', 'Sum'),
            'currency_id' => Yii::t('labels', 'Currency'),
            'line' => Yii::t('labels', 'Line'),
        );
    }

    public function printDetails() {
        $model = PaymentType::model()->findByPk($this->type);
        $form = new $model->value;

        $attrs = DocchequesEav::model()->findAllByAttributes(array("doc_id" => $this->doc_id, "line" => $this->line));
        //$text='';

        return $form->line($attrs);
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search() {

        $criteria = new CDbCriteria;

        $criteria->compare('doc_id', $this->doc_id, true);
        $criteria->compare('type', $this->type);
        $criteria->compare('line', $this->line);
        $criteria->compare('currency_id', $this->currency_id, true);

        if ($this->bank_refnum == '') {
            $criteria->addCondition('bank_refnum IS NULL');
            $criteria->addCondition('bank_refnum =""', 'OR');
        }

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'pagination' => array('pageSize' => 50),
        ));
    }

    public function depositSearch() {

        $criteria = new CDbCriteria;


        $criteria->addCondition('bank_refnum IS NULL');
        $criteria->addCondition('(type = 1 OR type = 2)', 'AND');

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'pagination' => array('pageSize' => 50),
        ));
    }

}
