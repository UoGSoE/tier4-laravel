@extends('layouts.app')

@section('content')

<h3 class="title is-3">
    Import project students
</h3>

<div class="box">
    <p>
        Format of spreadsheet should match the following:
    </p>
    <p>
        <code>
            todo | todo | todo
        </code>
    </p>
    <hr>
    <form action="{{ route('admin.import.project-students.store') }}" method="post" enctype="multipart/form-data">
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
