@extends('layout')

@section('dashboard-content')

<h1>Users</h1>
        <!-- Table Row -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Task Overview</h5>
                        <div class="table-responsive">
                            <!-- Search Input -->
                            <div class="d-flex justify-content-end mb-3">
                                <div class="search-container">
                                    <input type="text" id="searchInput" class="form-control search-box" placeholder="Search...">
                                    <span class="search-icon"><i class="material-icons-outlined">search</i></span>
                                </div>
                            </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Username</th>
                                <th>Full Names</th>
                                <th>Email</th>
                                <th>Wallet Bal</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Johnny112</td>
                                <td>John Doe</td>
                                <td>Johnnydoe@gmail.com</td>
                                <td>#400</td>
                                <td>
                                    <span class="status completed">Verified</span>
                                </td>
                                
                                <td>
                                    <div class="dropdown">
                                        <span class="action-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8942;</span>
                                        <section class="dropdown-menu">
                                            <a class="dropdown-item" href="edit-users/{id}">Edit</a>
                                            <a class="dropdown-item" href="#">Suspend</a>
                                            <a class="dropdown-item" href="#">Block</a>
                                        </section>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Tessy424</td>
                                <td>Tessy Johnson</td>
                                <td>Tessyjoh@gmail.com</td>
                                <td>#800</td>
                                <td>
                                    <span class="status completed">Verified</span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <span class="action-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8942;</span>
                                        <section class="dropdown-menu">
                                            <a class="dropdown-item" href="edit.html">Edit</a>
                                            <a class="dropdown-item" href="#">Suspend</a>
                                            <a class="dropdown-item" href="#">Block</a>
                                        </section>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Tommyjay</td>
                                <td>Tom Cruise</td>
                                <td>tomcruise@gmail.com</td>
                                <td>#200</td>
                                <td>
                                    <span class="status cancel">Not Verified</span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <span class="action-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8942;</span>
                                        <section class="dropdown-menu">
                                            <a class="dropdown-item" href="edit.html">Edit</a>
                                            <a class="dropdown-item" href="#">Suspend</a>
                                            <a class="dropdown-item" href="#">Block</a>
                                        </section>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>James222</td>
                                <td>James Smith</td>
                                <td>jamessmith@gmail.com</td>
                                <td>#800</td>
                                <td>
                                    <span class="status cancel">Not Verified</span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <span class="action-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8942;</span>
                                        <section class="dropdown-menu">
                                            <a class="dropdown-item" href="edit.html">Edit</a>
                                            <a class="dropdown-item" href="#">Suspend</a>
                                            <a class="dropdown-item" href="#">Block</a>
                                        </section>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>Petergreg</td>
                                <td>Peter Gregg</td>
                                <td>petergregg44@gmail.com</td>
                                <td>#100</td>
                                <td>
                                    <span class="status cancel">Not Verified</span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <span class="action-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8942;</span>
                                        <section class="dropdown-menu">
                                            <a class="dropdown-item" href="edit.html">Edit</a>
                                            <a class="dropdown-item" href="#">Suspend</a>
                                            <a class="dropdown-item" href="#">Block</a>
                                        </section>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>Pierre322</td>
                                <td>Pierre Morgan</td>
                                <td>Pierremorg22@gmail.com</td>
                                <td>#900</td>
                                <td>
                                    <span class="status completed">Verified</span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <span class="action-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&#8942;</span>
                                        <section class="dropdown-menu">
                                            <a class="dropdown-item" href="edit.html">Edit</a>
                                            <a class="dropdown-item" href="#">Suspend</a>
                                            <a class="dropdown-item" href="#">Block</a>
                                        </section>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
</div>
                    </div>
                </div>
            </div>
        </div>

@stop