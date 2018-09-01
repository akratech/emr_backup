@extends('layouts.app')

@section('view.stylesheet')
<style>
    .pediatric-immunizations-schedule .received {
        background-color: lightgreen;
    }
    .pediatric-immunizations-schedule .due {
        background-color: lightyellow;
    }
    .pediatric-immunizations-schedule .main-cell {
        font-weight: bold;
        width: 10%;
    }
    .pediatric-immunizations-schedule td {
        text-align: center;
    }
    .pediatric-immunizations-schedule td .recommended-age {
        display: block;
        font-size: .8em;
    }
    #search_patient_form{
        display: none;
    }
/*    body {
    overflow:hidden;
    }
    iframe .navbar{
        display: none;
    }*/
</style>
@endsection

@section('content')

<div class="wrapper" onload="my_init()">
			<h2 class="white">Online Video Conference with Dr. dev</h2>

			<div class="row">

				<div class="col-md-8">

					<div class="panel panel-primary">
						<div class="panel-heading">
							<h3 id="partnerName" class="panel-title"></h3>


						</div>
						<div class="panel-body panel-video">
							<div id="subscriber" style="min-height:500px; width: 100%;">

								<video id="partner" style="min-height:500px; width: 100%;background-color: #000;"></video>
							</div>
						</div>
					</div>


				</div>
				<!-- col-md-8 -->

				<div class="col-md-4">
					<div class="panel panel-primary">
						<div class="panel-heading">
							<h3 class="panel-title">Me</h3>

						</div>
						<div class="panel-body panel-video">
							<div id="publisher" style="min-height: 275px;width: 100%;">

								<video id="self" muted="muted" volume="0" style="width: 100%;min-height: 222px;background-color: #000;"></video>
								</video>
							</div>
							<!--<button type="button" class="btn btn-success" id="publishVideo" style="display:block">Publish video</button>-->
							<!--<button type="button" class="btn btn-warning" id="unpublishVideo" style="display:block">Unpublish video</button>-->
							<button type="button" onclick="onLeaveRoom()" class="btn btn-default btn-leave" id ="disconnectLink" style="display:block">Leave room</button
						</div>
					</div>

					<div class="clearfix"></div>

					<div class="panel panel-primary">
						<div class="panel-heading">
							<h3 class="panel-title">Notifications</h3>
						</div>
						<div class="panel-body">
							<div id="notification">
								<div id="notice-0" class="alert alert-dismissable alert-danger video-notice">You've restricted audio and video permissions.</div>
							</div>
						</div>
					</div>
				</div>
				<!--notice and publisher section -->

			</div>
			<!-- row -->


			<div class="row">
				<div class="col-md-12">
					<div class="panel panel-info">
						<div class="panel-heading purple-bg">
							<h3 class="panel-title">Messages</h3>
						</div>
						<div class="panel-body">
							<div id="messages" style="overflow-y: scroll;">

							</div>
							
								<div class="input-group has-success">
									<input autocomplete="off" type="text" id="message" name="message" class="form-control">
									<span class="input-group-btn">
										<input type="button" class="btn btn-success" onclick="sendmessage()" id="submit" value="Submit">
									</span>
								</div>
								<!-- /input-group -->
							
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- wrapper -->
	</div>
<iframe id="full-screen-me" frameborder="0" style="overflow:hidden;height:100%;width:100%" height="100%" width="100%" src={{ route('patient2') }}></iframe>
@endsection
@section('view.scripts')
	<script type="text/javascript" src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
<script src="{{asset('assets/js/rtc/socket.io.js')}}" type="text/javascript"></script>
<script src="{{asset('assets/js/rtc/easyrtc.js')}}" type="text/javascript"></script>
<script src="{{asset('assets/js/rtc/main.js')}}" type="text/javascript"></script>
<script language="javascript">
        function autoResizeDiv()
        {
            document.getElementById('full-screen-me').style.height = window.innerHeight +'px';

        }
        window.onresize = autoResizeDiv;
        autoResizeDiv();
    </script>
    @endsection