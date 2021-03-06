<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Laravel</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css" integrity="sha384-XdYbMnZ/QjLh6iI4ogqCTaIjrFk87ip+ekIjefZch0Y+PvJ8CDYtEs1ipDmPorQ+" crossorigin="anonymous">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:100,300,400,700">

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
    {{-- <link href="{{ elixir('css/app.css') }}" rel="stylesheet"> --}}

    <link href="{{ url('css/core.css') }}" rel="stylesheet">
    <link href="{{ url('css/app.css') }}" rel="stylesheet">
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
                <ul id="mainMenu" class="nav navbar-nav">
                    <li><a href="{{ url('/team') }}">Team</a></li>
                    @if(auth()->user()->match)
                        <li><a href="{{ url('/match') }}">Match vs {{ auth()->user()->matchOpponent->name }}</a></li>
                    @endif
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

            <div id="challengesFrom">
                @foreach(auth()->user()->challengesFrom as $i)
                    <div data-id="{{ $i->userTo->id }}" class="user {{
                        $i->userTo->online ? 'online' : 'offline'
                    }}{{
                        $i->userTo->match ? ' match' : ''
                    }}">
                        <span class="name">{{ $i->userTo->name }}</span>
                        <span class="status"></span>
                        <a class="challengeRemove" href="#">Удалить</a>
                    </div>
                @endforeach
            </div>
            <div id="challengesTo">
                @foreach(auth()->user()->challengesTo as $i)
                    <div data-id="{{ $i->userFrom->id }}" class="user {{
                        $i->userFrom->online ? 'online' : 'offline'
                    }}{{
                        $i->userFrom->match ? ' match' : ''
                    }}">
                        <span class="name">{{ $i->userFrom->name }}</span>
                        <span class="status"></span>
                        <a class="play" href="#">Играть</a>
                        <a class="challengeRemove" href="#">Удалить</a>
                    </div>
                @endforeach
            </div>

            <div id="stdElements">
                <div data-id="" class="user online">
                    <span class="name"></span>
                    <span class="status"></span>
                    <a class="play" href="#">Играть</a>
                    <a class="challengeRemove" href="#">Удалить</a>
                </div>
            </div>

        </div>
    </nav>

    @yield('content')

    <!-- JavaScripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.3/jquery.min.js" integrity="sha384-I6F5OKECLVtK/BL+8iSLDEHowSAfUo76ZL9+kGAgTRdiByINKJaqTPH/QVNS1VDb" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
    {{-- <script src="{{ elixir('js/app.js') }}"></script> --}}

    <script src="{{ url('//' . $_SERVER['HTTP_HOST'] . ':8080/socket.io/socket.io.js') }}"></script>
    <script src="{{ url('js/core.js') }}"></script>
    <script src="{{ url('js/app.js') }}"></script>
    @yield('js')

</body>
</html>
