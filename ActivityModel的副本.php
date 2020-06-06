<?php

namespace App\Api\Models;

use App\Api\Models;
use DB;

class  ActivityModel extends  BaseModel{

    //首页
    public  function  activity($inputs)
    {
        return $data =  DB::table('activity')
            ->select('activityname','activitytitle','createtime','endtime')
            ->where($inputs)
            ->first();
    }
    //首页参加人员
    public  function  activitymember()
    {
        return $data =  DB::table('member as m')
            ->join('activity_works as a','a.uid','=','m.id')
            ->select('m.nickname','m.headpic')
            ->limit(4)
            ->get();
    }

    //用户数
    public  function  membernumber()
    {
        return $data =  DB::table("member")->count('id');
    }

    //作品列表
    public  function  workslist($inputs)
    {
        return $data =  DB::table('activity_works as w')
            ->join('activity as a','a.id','=','w.a_id')
            ->join('member as m','m.id','=','w.uid')
            ->select('w.id','w.title','w.file','a.activityname','m.realname')
            ->where("w.a_id","=",$inputs['id'])
            ->get();
    }

    //奖品信息
    public  function  prize()
    {
      return  $data = DB::table('activity_prize')
                    ->select('name','prize','img')
                    ->get();
    }

    //首页实例图片
    public  function  activityimg()
    {
        return    $data = DB::table('activity_img')
                    ->select()
                    ->where()
                    ->get();
    }
    //作品详情
    public  function  worksdetail($inputs)
    {
        return $data =  DB::table('activity_works as w')
            ->join('activity as a','a.id','=','w.a_id')
            ->join('member as m','m.id','=','w.uid')
            ->where('w.id','=',$inputs['id'])
            ->select('w.id','w.title','w.fabulous','a.activitytitle','w.status','a.activityname','a.starttime','a.endtime','m.realname')
            ->first();

    }
    //点赞加+1
    public  function  praise($inputs)
    {
     $data = DB::table('activity_works')
            ->where("id","=",$inputs['id'])
            ->update($inputs);
     return $data;
    }

    //投票数+1
    public function  worksvote($inputs)
    {
        $data = DB::table('activity_votes')
            ->where("worksid","=",$inputs['worksid'])
            ->update($inputs);
        return $data;
    }

    //查询今天是否已经投票
    public  function  voterrecord($inputs)
    {
        $data = DB::table('activity_vote_record')
            ->where(["voter"=>$inputs['voter'],"createtime"=>$inputs['createtime']])
            ->first();
        return $data;
    }

    //投票记录写入
    public  function  voterecordadd($inputs)
    {
        $data = DB::table('activity_vote_record')->insert($inputs);
        return $data;
    }

    //作品新增
    public  function  add($inputs)
    {
            DB::table('activity_works')->insert();
    }

    //活动获奖作品
    public  function  winning($inputs)
    {
        $data = DB::table('activity_votes as v')
            ->join("activity_works as w","w.id","=","v.worksid")
            ->join("member as m","m.id","=","v.u_id")
            ->join("activity as a","a.id","=","v.a_id")
            ->where("a.id","=",$inputs['id'])
            ->orderBy('v.invotes','desc')
            ->select(
                "m.realname"
                ,"w.title"
                ,"m.headpic"
                ,"w.file"
                ,"w.content"
                ,"v.invotes"
            )
            ->limit(5)
            ->get();
        return $data;
    }

    // 移动端分享
    /**
     * @param $mid
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivityShare($mid){

         //投票阶段解开注释
       $user_works = DB::table("activity_works as wa ")
            ->join("activity as a","wa.a_id","=","a.id")
            ->where([['uid','=',$mid]])
            ->select("wa.title","wa.id",'a.id as aid','a.status')
            ->first();
        $com = getenv('APP_URL');
        $comArr = explode("/",$com);
        unset($comArr[count($comArr)-1]);
        unset($comArr[count($comArr)-1]);
        $com_new =implode("/",$comArr);
         if($user_works){
            if($user_works->status == 2){
            $user_works->content = '快来支持我一下吧！';
            // 获取图片
            $image =   DB::table("activity_works_img")->where([['worksid',"=",$user_works->id]])->select("url")->first();
            // 赋值路径
            $user_works->icon =$image->url;
            // link  http://bhzj.binghuozhijia.com/test/bhzj/api/wechat/workdetail
            $user_works->link = $com_new.'/h5-wechat/h5-weixin/design_list.html?uid='.$mid.'&cuid='.$user_works->id;
            // 添加票数
            $user_works->votesnum =  !empty($ids = DB::table("activity_vote_record")->select(DB::raw("count(id) as ids"))->where([['worksid','=',$user_works->id]])->first())? $ids->ids."票" : '0票';
            // 添加用户基本信息
             $member = DB::table("member")->where([['id','=',$mid]])->select("headpic","nickname")->first();
             if($member){
                 $user_works->headpic = $member->headpic;
                 $user_works->nickname = $member->nickname;
             }else{
                 $user_works->headpic = '';
                 $user_works->nickname = '';
             }
            unset($user_works->id);
             }
        }else{
        if(!$user_works){
            $worksid = 2; // worksid 为什么是2 -- 因为测试服中的id是2 -- 改为对应的正式服就好了

        }else{
            $worksid = $user_works->aid;
        }
        //http://bhzj.binghuozhijia.com/test/h5-wechat/h5-weixin/download.html?uid=1047&id=2
            $user_works = new \stdClass();
            $user_works->title = "设计大赛人人有奖";
            $user_works->content = '快来参加吧！';
            $user_works->icon =getenv('APP_URL').'public/activity/img/banner_one@2x.png';
            $user_works->link = $com_new.'/h5-wechat/h5-weixin/download.html?uid='.$mid.'&id='.$worksid;
            $user_works->headpic = '';
            $user_works->nickname = '';
            $user_works->votesnum = '';
       }
        return $user_works;

    }
}
