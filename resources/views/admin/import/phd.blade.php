@extends('layouts.app')

@section('content')

<h3 class="title is-3">
    Import PhD Students
</h3>

<div class="box">
    <p>
        Format of spreadsheet should match the following:
    </p>
    <p>
        <code>
            Matric | Surname | Forenames | Email | Supervisor GUID | Supervisor Surname | Supervisor Forenames | Supervisor Email
        </code>
    </p>
    <hr>
    <form action="{{ route('admin.import.phds.store') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="field is-grouped">
            <div class="control">
                <div class="file">
                    <label class="file-label">
                      <input class="file-input" type="file" name="sheet">
                      <span class="file-cta">
                        <span class="file-label">
                          Choose a file…
                        </span>
                      </span>
                    </label>
                </div>
            </div>
            <div class="control">
                <button class="button is-info">Import</button>
            </div>
        </div>
    </form>
</div>

@endsection
