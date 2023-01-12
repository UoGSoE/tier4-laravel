<nav id="navbar" class="navbar is-info" role="navigation" aria-label="main navigation">
    <div class="navbar-brand">
        <a class="navbar-item has-text-weight-semibold" href="/">
            Tier4
        </a>

        <a role="button" class="navbar-burger burger" aria-label="menu" aria-expanded="false" data-target="navbarBasicExample">
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </a>
    </div>

    <div id="navbarBasicExample" class="navbar-menu">
        <div class="navbar-start">
            @admin
            <div class="navbar-item has-dropdown is-hoverable">
                <a class="navbar-link">
                    Admin
                </a>

                <div class="navbar-dropdown">
                    <a class="navbar-item" href="{{ route('reports.overdue', ['type' => \App\Models\Student::TYPE_PHD]) }}">
                        Overdue Phd students
                    </a>
                    <a class="navbar-item" href="{{ route('reports.overdue', ['type' => \App\Models\Student::TYPE_POSTGRAD_PROJECT]) }}">
                        Overdue Postgrad Project students
                    </a>
                    <a class="navbar-item" href="{{ route('reports.supervisors') }}">
                        Supervisors
                    </a>
                    <hr class="navbar-divider">
                    <a class="navbar-item" href="{{ route('admin.import.phds.create') }}">
                        Import PhD Students
                    </a>
                    <hr class="navbar-divider">
                    <a class="navbar-item" href="{{ route('admin.options.edit') }}">
                        Options
                    </a>
                    <a class="navbar-item" href="{{ route('admin.admins.edit') }}">
                        Manage Admins
                    </a>
                </div>
            </div>
            @endadmin
        </div>

        <div class="navbar-end">
            @impersonating($guard = null)
                <div class="navbar-item">
                    <a class="button" href="{{ route('impersonate.leave') }}">Stop impersonating</a>
                </div>
            @endImpersonating
            <div class="navbar-item">
                <div class="buttons">
                    <form method="POST" action="/logout">
                        @csrf
                        <button class="button is-dark">Log Out {{ auth()->user()->full_name }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>
