@extends('layouts.app')
@section('content')

<h3 class="title is-3">
    Details for student <a href="{{ route('reports.student', $student) }}">{{ $student->full_name }}</a>
    (<a href="mailto:{{ $student->email }}">{{ $student->email }}</a>)
</h3>

<div class="box">
    <form action="{{ route('admin.student.update', $student) }}" method="post">
        @csrf
        <div class="field">
            <label class="label">Forenames</label>
            <div class="control">
                <input class="input" type="text" name="forenames" value="{{ $student->forenames }}">
            </div>
        </div>
        <div class="field">
            <label class="label">Surname</label>
            <div class="control">
                <input class="input" type="text" name="surname" value="{{ $student->surname }}">
            </div>
        </div>
        <div class="field">
            <label class="label">Email</label>
            <div class="control">
                <input class="input" type="text" name="email" value="{{ $student->email }}">
            </div>
        </div>
        <div class="field">
            <div class="control">
                <input type="hidden" name="is_active" value="0">
                <label class="checkbox">
                    <input type="checkbox" name="is_active" @checked($student->isActive()) value="1">
                    <b>Active?</b> <span>(ie, is the student still here?)</span>
                </label>
            </div>
        </div>
        <div class="field">
            <div class="control">
                <input type="hidden" name="is_silenced" value="0">
                <label class="checkbox">
                    <input type="checkbox" name="is_silenced" @checked($student->isSilenced()) value="1">
                    <b>Silenced?</b> <span>(This will prevent emails about this student being sent, eg they are known to have returned home for a period)</span>
                </label>
            </div>
        </div>
        <div class="field">
            <label class="label">Reason they are silenced (Required if student is silenced)</label>
            <div class="control">
                <input class="input" type="text" name="silenced_reason" value="{{ $student->silenced_reason }}">
            </div>
        </div>
        <div class="field">
            <label class="label">Add a note</label>
            <p class="help">Please don't include any personal details such as the name of an illness, an address, phone number etc.</p>
            <div class="control">
              <textarea class="textarea" name="new_note"></textarea>
            </div>
        </div>
        <div class="field">
            <div class="control">
                <button class="button">Save</button>
            </div>
        </div>
    </form>
</div>

@if ($student->notes->count() > 0)
    <div class="box">
        <h3 class="title is-3">Existing Notes</h3>
        <ul>
            @foreach ($student->notes as $note)
                <article class="message">
                    <div class="message-header has-background-grey" x-data="{
                        open: false,
                        deleteNote() {
                            document.querySelector('form[action=\'{{ route('admin.student.notes.delete', $note) }}\']').submit();
                        }
                    }">
                    <form style="display: none" action="{{ route('admin.student.notes.delete', $note) }}" method="post">
                        @csrf
                    </form>
                    <span>{{ $note->created_at->format('d/m/Y') }} by {{ $note->user?->full_name }}</span>
                        <button x-on:click.prevent="open = true" class="delete is-pulled-right" title="Delete Note" aria-label="delete"></button>
                        <div class="modal" x-bind:class="open ? 'is-active' : ''">
                            <div x-on:click.prevent="open = false" class="modal-background"></div>
                            <div class="modal-card">
                              <header class="modal-card-head">
                                <p class="modal-card-title">Confirm</p>
                                <button x-on:click.prevent="open = false" class="delete" aria-label="close"></button>
                              </header>
                              <section class="modal-card-body has-text-black">
                                Really delete this note?
                              </section>
                              <footer class="modal-card-foot">
                                <button x-on:click.prevent="deleteNote()" class="button is-success">Yes</button>
                                <button x-on:click.prevent="open = false" class="button">No</button>
                              </footer>
                            </div>
                        </div>
                    </div>
                    <div class="message-body">
                        <form action="{{ route('admin.student.notes.update', $note) }}" method="post">
                            @csrf
                            <div class="field">
                                <div class="control">
                                    <textarea class="textarea" name="body">{{ $note->body }}</textarea>
                                </div>
                            </div>
                            <div class="field">
                                <div class="control">
                                    <button class="button">Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            @endforeach
        </ul>
    </div>
@endif

@endsection
