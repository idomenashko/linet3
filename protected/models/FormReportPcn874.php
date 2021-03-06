<?php

/***********************************************************************************
 * The contents of this file are subject to the Mozilla Public License Version 2.0
 * ("License"); You may not use this file except in compliance with the Mozilla Public License Version 2.0
 * The Original Code is:  Linet 3.0 Open Source
 * The Initial Developer of the Original Code is Adam Ben Hur.
 * All portions are Copyright (C) Adam Ben Hur.
 * All Rights Reserved.
 ************************************************************************************/
class FormReportPcn874 extends CFormModel{
    public $year;
    public $from_date;
    public $to_date;
    
    public function rules(){
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			
			array('to_date, from_date, year', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('to_date, from_date, year', 'safe', 'on'=>'search'),
		);
	}
    
    private function calc($account_type){
        $criteria=new CDbCriteria;
        $criteria->condition="type = :type";
        $criteria->params=array(
            ':type' => $account_type,
          );

        $accounts= Accounts::model()->findAll($criteria);
        $sum=0;
        $data=array();
        foreach($accounts as $account){
                
                $sum=$account->getTotal($this->from_date.":00",$this->to_date.":00"); 
                if($sum!=0)
                    $data[]=array('id'=>$account->id,'name'=>$account->name,'sum'=>$sum,'id6111'=>$account->id6111);
        }
        return $data;
    }
    
    protected function start($array){
        //stringfy a company by pcn874
        //+A1    type
        //+N9    vatid
        //+N6    file contacet date YYYYMM
        //+N1    1
        //+N8    file date YYYYMMDD
        //+A1    +/- total without vat
        //+N11   total without vat
        //+A1     +/- total vat    
        //+N9    total vat
        //+A1    +/- total without vat not standert
        //+N11   total without vat not standert
        //+A1    +/- vat not standert
        //+N9    doc vat not standert
        //+N9    doc count imcome
        //
        //+A1    +/- exclude vat*
        //+N11   total exclude vat*
        //+A1    +/- Other outcome vat
        //+N9    total Other Outcomes vat
        //+A1    +/- Assets Outcome vat
        //+N9    total Assets Outcome vat
        //+N9    doc count total outcome
        //A1    +/- to pay
        //N9    total to pay
        //
        //
        extract($array);
        $yiidatetimesec=Yii::app()->locale->getDateFormat('yiidatetimesec');
        
        $companyid=Settings::model()->findByPk('company.vat.id')->value;
        $condate=date('Ym',CDateTimeParser::parse($this->from_date.":00",$yiidatetimesec));;
        $filedate=date('Ymd');                                  //+N8    file date YYYYMMDD
        $t_pn=($t_wovat>=0)?"+":"-";                                               //+A1    +/- total without vat
        $t_vat_pn=($t_vat>=0)?"+":"-";                                           //+A1     +/- total vat
        $ns_t_pn=($ns_t_wovat>=0)?"+":"-";                                            //+A1    +/- total without vat not standert
        $ns_t_vat_pn=($ns_t_vat>=0)?"+":"-";                                        //+A1    +/- vat not standert
        
        $t_exclude_vat_pn=($t_exclude_vat>=0)?"+":"-";
        $ot_vat_pn=($ot_vat>=0)?"+":"-";
        $at_vat_pn=($at_vat>=0)?"+":"-";
        $sum_pn=($sum>=0)?"+":"-";
        
        return sprintf("O%09d%06d1%08d%01s%011d%01s%09d%01s%011d%01s%09d%01s%011d%01s%09d%01s%09d%09d%01s%09d",
            $companyid, $condate,$filedate,$t_pn, $t_wovat,$t_vat_pn,$t_vat,$ns_t_pn,$ns_t_wovat,$ns_t_vat_pn,$ns_t_vat,$i_doc_count,
            $t_exclude_vat_pn,$t_exclude_vat,$ot_vat_pn,$ot_vat,$at_vat_pn,$at_vat,$o_doc_count,$sum_pn,$sum
        );
        
        
    }

    protected function end(){
        //A1    type
        //N9    vatid
        $companyid=Settings::model()->findByPk('company.vat.id')->value;
        return sprintf("Z%09d",
			$companyid);
    }
    



    public function make(){
        $text='';
            
        $yiidatetimesec=Yii::app()->locale->getDateFormat('yiidatetimesec');
        $phpdbdatetime=Yii::app()->locale->getDateFormat('phpdbdatetime');

        $from_date=date($phpdbdatetime,CDateTimeParser::parse($this->from_date.":00",$yiidatetimesec));
        $to_date=date($phpdbdatetime,CDateTimeParser::parse($this->to_date.":00",$yiidatetimesec));
        
        $types=array(3,4,9,11,13,14);
        $criteria=new CDbCriteria;
        $criteria->condition="issue_date BETWEEN :from_date AND :to_date";
        $criteria->params=array(
            ':from_date' => $from_date,
            ':to_date' => $to_date,
          );
        $criteria->compare('doctype', $types);
        
        $docs= Docs::model()->findAll($criteria);
        $vatper=18;
        $start=array('t_wovat'=>0,'t_vat'=>0,'ns_t_wovat'=>0,'ns_t_vat'=>0,'i_doc_count'=>0,
            't_exclude_vat'=>0,'ot_vat'=>0,'at_vat'=>0,'o_doc_count'=>0,'sum'=>0,
            
            );
        foreach($docs as $doc){
                $text.=$doc->pcn874()."\n"; 
                
                if(in_array($doc->doctype, array(3,4,9,11))){
                    if($doc->sub_total!=0){
                        if((($doc->vat/$doc->sub_total)*100) ==$vatper){
                            $start['t_wovat']+=$doc->sub_total;
                            $start['t_vat']+=$doc->vat;
                        }else if($doc->vat==0){
                            $start['t_exclude_vat']+=$doc->sub_total;
                        }else{
                            $start['ns_t_wovat']+=$doc->sub_total;
                            $start['ns_t_vat']+=$doc->vat;
                        }
                    }    
                    
                    $start['i_doc_count']++;
                    
                    
                }
                if(in_array($doc->doctype, array(13,14))){        
                    if($doc->doctype==13)
                         $start['ot_vat']+=$doc->vat;
                    else 
                         $start['at_vat']+=$doc->vat;
                    $start['o_doc_count']++;
                }
        }
        $start['sum']=$start['t_vat']+$start['ns_t_vat'];
        
        return $this->start($start)."\n".$text.$this->end();      
    }
    
    
    
    public function search(){
        $data= array();
        //echo $this->make();
        //Yii::app()->end();
        //$data=$this->calc(3);//incomes
        //$data=array_merge($data,$this->calc(4));//outcomes
        
        
        return new CArrayDataProvider($data,
                 array(
                                'pagination'=>array(
                                    'pageSize'=>100,
                            ),
                )             
          );
    }
    //put your code here
}
