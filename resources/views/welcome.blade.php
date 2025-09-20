<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>E-Docket System - WRD</title>
        
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <style>
            .custom-card-hover {
                transition: transform 0.2s ease-in-out;
            }
            .custom-card-hover:hover {
                transform: translateY(-5px);
            }
        </style>
    </head>
    <body class="bg-light">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="#">OSE E-Docket System</a>
                <div class="navbar-nav ms-auto">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="nav-link">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="nav-link">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="nav-link">Register</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </nav>

        <div class="container">
            <div class="col my-5">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card shadow custom-card-hover h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">About OSE eDocket</h5>
                                <p class="card-text">
                                    This beta version of the OSE Hearing Unit eDocket remains under active development.
                                    Efforts are being made to further populate the database underlying the edocket with
                                    pleadings and orders, and all other relevant case material including but not limited
                                    to filed Applications, Denial and Rejection letters, Letters of Protest, Notices of Publication,
                                    Affidavits of Publication, Compliance Orders, and other Pleadings and Orders related
                                    to past and current litigated matters.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow custom-card-hover h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">FAQs</h5>
                                <p class="card-text">Is my document considered filed the day that I file it, or the day that the docket clerk accepts it?</p>
                                <p class="card-text">How is a case initiated?</p>
                                <p class="card-text">Who can file a document?</p>
                                <p class="card-text">How do I name a document: </p>
                                <p class="card-text">How do I file a document?  </p>
                                <a href="#" class="btn btn-outline-primary mt-auto">Find Answers</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow custom-card-hover h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Reference Material</h5>
                                <p class="card-text"><a href="https://nmonesource.com/nmos/en/a/s/index.do?cont=chapter+72+water+code">NMSA 1978, Chapter 72 Water Code</a></p>
                                <p class="card-text"><a href="https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c25">Title 19 Chapter 25, Part 1 New Mexico Administrative Code (19.25.1.NMAC) :
                                        Administration and Use of Water-General Provisions</a></p>
                                <p class="card-text"><a href="https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c25p1">Title 19, Chapter 25, Part 2 New Mexico Administrative Code (19.25.2 NMAC): Hearing Unit Procedures</a></p>
                                <p class="card-text"><a href=" https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c26">Title 19 Chapter 26 Surface Water Rules and Regulations</a></p>
                                <p class="card-text"><a href="https://nmonesource.com/nmos/nmac/en/item/18057/index.do#t19c27">Title 19 Chapter 27 Underground Water Rules and Regulations</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>