<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="jwt" content="{{ $jwt }}">

    <title>Laravel</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css" integrity="sha384-XdYbMnZ/QjLh6iI4ogqCTaIjrFk87ip+ekIjefZch0Y+PvJ8CDYtEs1ipDmPorQ+" crossorigin="anonymous">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:100,300,400,700">

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
    {{-- <link href="{{ elixir('css/app.css') }}" rel="stylesheet"> --}}

    <link href="{{ url('css/core.css') }}" rel="stylesheet">
    @yield('css')

    <style>
        body {
            font-family: 'Lato';
        }

        .fa-btn {
            margin-right: 6px;
        }
    </style>
</head>
<body id="app-layout">
    @if($errors->any())
    <div id="errors" class="popup">
        <div class="popup_content">
            @foreach($errors->all() as $item)
            <p>{{ $item }}</p>
            @endforeach
        </div>
    </div>
    @endif
    <nav class="navbar navbar-default navbar-static-top">
        <div class="container">
            <div class="navbar-header">

                <!-- Collapsed Hamburger -->
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
                    <span class="sr-only">Toggle Navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <!-- Branding Image -->
                <a class="navbar-brand" href="{{ url('/') }}">
                    Laravel
                </a>
            </div>

            <div class="collapse navbar-collapse" id="app-navbar-collapse">
                <!-- Left Side Of Navbar -->
                <ul class="nav navbar-nav">
                    <li><a href="{{ url('/home') }}">Home</a></li>
                    <li><a href="{{ url('/team') }}">Team</a></li>
                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="nav navbar-nav navbar-right">
                    <!-- Authentication Links -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                            {{ Auth::user()->name }} <span class="caret"></span>
                        </a>

                        <ul class="dropdown-menu" role="menu">
                            <li><a href="{{ url('/logout') }}"><i class="fa fa-btn fa-sign-out"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    @yield('content')

    <!-- JavaScripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.3/jquery.min.js" integrity="sha384-I6F5OKECLVtK/BL+8iSLDEHowSAfUo76ZL9+kGAgTRdiByINKJaqTPH/QVNS1VDb" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
    {{-- <script src="{{ elixir('js/app.js') }}"></script> --}}

    <script src="{{ url('js/core.js') }}"></script>
    <script src="{{ url('js/app.js') }}"></script>
    @yield('js')






    <script src="http://localhost:8080/socket.io/socket.io.js"></script>
 
<style type="text/css">
    #messages{
        border: 1px solid black;
        height: 300px;
        margin-bottom: 8px;
        overflow: scroll;
        padding: 5px;
    }
</style>
<div class="container spark-screen">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">Chat Message Module</div>
                <div class="panel-body">
 
                <div class="row">
                    <div class="col-lg-8" >
                      <div id="messages" ></div>
                    </div>
                    <div class="col-lg-8" >
                            <form action="sendmessage" method="POST">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" >
                                <input type="hidden" name="user" value="{{ Auth::user()->name }}" >
                                <textarea class="form-control msg"></textarea>
                                <br/>
                                <input type="button" value="Send" class="btn btn-success send-msg">
                            </form>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var socket = io.connect('http://localhost:8080');
    /*socket.on('message', function (data) {
        data = jQuery.parseJSON(data);
        console.log(data.user);
        $( "#messages" ).append( "<strong>"+data.user+":</strong><p>"+data.message+"</p>" );
      });*/


socket.on('connect', function () {
  socket
    .emit('authenticate', {token: $('meta[name="jwt"]').attr('content')}) //send the jwt
    .on('authenticated', function () {
      console.log("authorized!!!");
    })
    .on('unauthorized', function(msg) {
      console.log("unauthorized: " + JSON.stringify(msg.data));
      throw new Error(msg.data.type);
    })
});

socket.on('message', function (data) {
        data = jQuery.parseJSON(data);
        console.log(data.user);
        $( "#messages" ).append( "<strong>"+data.user+":</strong><p>"+data.message+"</p>" );
      });

    $(".send-msg").click(function(e){
        e.preventDefault();
        var token = $("input[name='_token']").val();
        var user = $("input[name='user']").val();
        var msg = $(".msg").val();
        if(msg != ''){
            $.ajax({
                url: '{!! URL::to("challenge") !!}',
                dataType: "json",
                data: {'_token':token,'message':msg,'user':user},
                success:function(data){
                    console.log(data);
                    $(".msg").val('');
                }
            });
        }else{
            alert("Please Add Message.");
        }
    })
</script>







</body>
</html>
