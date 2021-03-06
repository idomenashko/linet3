<?php
/***********************************************************************************
 * The contents of this file are subject to the Mozilla Public License Version 2.0
 * ("License"); You may not use this file except in compliance with the Mozilla Public License Version 2.0
 * The Original Code is:  Linet 3.0 Open Source
 * The Initial Developer of the Original Code is Adam Ben Hur.
 * All portions are Copyright (C) Adam Ben Hur.
 * All Rights Reserved.
 ************************************************************************************/
/**
 * This is the model class for table "accHist".
 *
 * The followings are the available columns in table 'accHist':
 * @property integer $id
 * @property string $account_id
 * @property string $dt
 * @property string $details
 *
 * The followings are the available model relations:
 * @property Accounts $account
 */
class AccHist extends CActiveRecord {

    private $dateDBformat = true;

    const table = '{{accHist}}';

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return self::table;
    }

    public function brief() {
        if (strlen($this->details) > 50) {
            $str=CHtml::link(CHtml::encode("[".Yii::t('app','Read More')."]"),Yii::app()->createAbsoluteUrl("/rm/update/".$this->id));
            return substr($this->details, 0, 50) . "...$str";
        } else {
            return $this->details;
        }
    }

    public function beforeSave() {
        if ($this->isNewRecord) {
            $this->dateDBformat = false;
        }

        if (!$this->dateDBformat) {
            $this->dateDBformat = true;
            $this->dt = date("Y-m-d H:i:s", CDateTimeParser::parse($this->dt, Yii::app()->locale->getDateFormat('yiishort')));
        }
        //return true;
        //echo $this->due_date.";".$this->issue_date.";".$this->modified;
        //Yii::app()->end();
        return parent::beforeSave();
    }

    public function afterSave() {
        if ($this->dateDBformat) {
            $this->dateDBformat = false;
            $this->dt = date(Yii::app()->locale->getDateFormat('phpshort'), strtotime($this->dt));
        }
        return parent::afterSave();
    }

    public function afterFind() {
        if ($this->dateDBformat) {
            $this->dateDBformat = false;
            $this->dt = date(Yii::app()->locale->getDateFormat('phpshort'), strtotime($this->dt));
        }


        return parent::afterFind();
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('account_id', 'length', 'max' => 11),
            array('dt, details, type', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, account_id, dt, details, type', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'Account' => array(self::BELONGS_TO, 'Accounts', 'account_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => Yii::t('labels', 'ID'),
            'account_id' => Yii::t('labels', 'Account'),
            'dt' => Yii::t('labels', 'Timestamp'),
            'details' => Yii::t('labels', 'Details'),
            'type' => Yii::t('labels', 'Type'),
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search() {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('account_id', $this->account_id, true);
        $criteria->compare('dt', $this->dt, true);
        $criteria->compare('details', $this->details, true);
        $criteria->compare('type', $this->account_id, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return AccHist the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

}
