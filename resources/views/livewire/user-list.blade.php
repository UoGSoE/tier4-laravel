<div>
    <div class="columns">
        <div class="column">
            <table class="table is-fullwidth is-striped is-hoverable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>PhD Emails</th>
                        <th>Postgrad Project Emails</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr id="user-row-{{ $user->id }}">
                            <td>{{ $user->full_name }}</td>
                            <td>
                                <div class="field">
                                    <div class="control">
                                        <div class="field">
                                            <div class="control">
                                              <label class="checkbox">
                                                <input type="checkbox" @checked($user->wants_phd_emails) wire:click.prevent="toggleWantsEmail({{ $user->id}}, 'wants_phd_emails')">
                                              </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="field">
                                    <div class="control">
                                        <div class="field">
                                            <div class="control">
                                              <label class="checkbox">
                                                <input type="checkbox" @checked($user->wants_postgrad_project_emails) wire:click.prevent="toggleWantsEmail({{ $user->id}}, 'wants_postgrad_project_emails')">
                                              </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if (auth()->id() != $user->id)
                                    <div x-data="{open: false}">
                                        <button x-on:click.prevent="open = true" class="button is-danger is-outlined is-small">
                                            Remove Admin
                                        </button>
                                        <div class="modal" x-bind:class="open ? 'is-active' : ''">
                                            <div x-on:click.prevent="open = false" class="modal-background"></div>
                                            <div class="modal-card">
                                              <header class="modal-card-head">
                                                <p class="modal-card-title">Please Confirm</p>
                                                <button x-on:click.prevent="open = false" class="delete" aria-label="close"></button>
                                              </header>
                                              <section class="modal-card-body">
                                                Really remove admin rights from {{ $user->full_name }}?
                                              </section>
                                              <footer class="modal-card-foot">
                                                <button x-on:click.prevent="$wire.demoteUser({{ $user->id }})" class="button is-success">Yes</button>
                                                <button x-on:click="open = false" class="button">No</button>
                                              </footer>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="column">
            <form action="" method="post">
                @csrf
                <label class="label">Username (GUID)</label>
                <div class="field has-addons">
                    <div class="control">
                        <input wire:model="username" type="text" class="input" name="username" placeholder="Username">
                    </div>
                    <div class="control">
                        <button wire:click.prevent="lookupUser" class="button is-info">Lookup</button>
                    </div>
                </div>
                @if ($error)
                    <p class="help is-danger">{{ $error }}</p>
                @endif
                <div class="field">
                    <label class="label">Email</label>
                    <div class="control">
                        <input wire:model="email" type="text" class="input" name="email" placeholder="Email" disabled>
                    </div>
                </div>
                <div class="field">
                    <label class="label">Forenames</label>
                    <div class="control">
                        <input wire:model="forenames" type="text" class="input" name="forenames" disabled>
                    </div>
                </div>
                <div class="field">
                    <label class="label">Surname</label>
                    <div class="control">
                        <input wire:model="surname" type="text" class="input" name="surname" disabled>
                    </div>
                </div>
                <div class="field">
                    <div class="control">
                        <button wire:click.prevent="createUser" class="button is-primary" @if (! $email) disabled @endif>Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
