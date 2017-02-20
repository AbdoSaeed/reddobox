@extends('layouts.master')

@section('content')
@if (Auth::user()->isFriendWith($user))
  <div style="min-height: 70%">
  <div id="profilecontent">
    <div class="row">
  <div class="col-sm-6 col-md-3 my">
    <div class="thumbnail">
    <img src="{{url('uploads/images/')}}/{{$user->avatar}}" width="250px" alt="...">
    <div class="caption">
    <center><h3><a href="/profile/{{$user->id}}">{{$user->fname}} {{$user->lname}}</h3></a></center>
        {{-- <ul style="list-style:none;padding-left:0">
        <li id="menu">
          <md-button href="/rate/social/{{$user->id}}" class="md-raised md-primary activebtn" style="background-color:#1D7F8D;">
            social evaluation
            </md-button>
            </li>
            <li>
            <md-button href="/rate/social/{{$user->id}}" class="md-raised md-primary mybtn" style="background-color:#272525;">
             social evaluation
          </md-button>
          </li>
          <li>
            <md-button href="/rate/professional/{{$user->id}}" class="md-raised md-primary mybtn" style="background-color:#272525;">
            Professional evaluation
          </md-button>
          </li>
          </ul> --}}
      </div>
    </div>
  </div>
  <script type="text/javascript">
    var from_id = {{Auth::id()}};
    var user_id = {{$user->id}};
  </script>
<div class="col-md-8" style=" height: auto; background-color:#edebeb;" ng-controller="socialRateCtrl">
<h3>Reddo {{$user->fname}} socially</h3>
<hr>

    <div ng-repeat="trait in social_traits">
      <md-checkbox ng-model="bool[trait.id]" ng-click="zeroRate(trait.id)"><h4>@{{trait.name}}</h4></md-checkbox>
      <md-slider-container ng-disabled="!bool[trait.id]">
        <md-icon md-svg-icon="device:brightness-low"></md-icon>
        <md-slider ng-model="ss[trait.id]" ng-change="changeBool(trait.id)" aria-label="Disabled 1" flex="" md-discrete="" ng-readonly="readonly"></md-slider>
      </md-slider-container>
    </div>
    <hr>
      <textarea ng-model="review" class="form-control" rows="5" required="required" placeholder="Leave a note.."></textarea>
    <center>
    <hr>
    <button type="button" id="social_submit" class="btn bg color btn-lg" id="load" data-loading-text="<i class='fa fa-spinner fa-spin '></i> Processing">Reddo</button>
    </center>
    <hr>
      </div>
    </div>
</div>

  </div>
@else
<center style="min-height: 70%">
  <h1>
    You're not friends.
  </h1>
</center>
@endif
@stop