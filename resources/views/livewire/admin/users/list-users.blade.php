<div>
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
            <h1 class="m-0">{{ __('app.menu-users') }}</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{route('home.home')}}">{{ __('app.menu-home') }}</a></li>
                <li class="breadcrumb-item"><a href="#">{{ __('app.menu-admin') }}</a></li>
                <li class="breadcrumb-item active">{{ __('app.menu-users') }}</li>
            </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex justify-content-end mb-2">
                    <button wire:click.prevent="addNew" class="btn btn-primary">
                        <i class="fa fa-plus-circle mr-1"></i>
                        {{ __('user.addNew') }}</button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('user.email') }}</th>
                                <th scope="col">{{ __('app.userRole') }}</th>
                                <th scope="col">{{ __('user.registered') }}</th>
                                <th scope="col">{{ __('app.options') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <th scope="row">{{ $user->id }}</th>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ __('roles.'.$user->role) }}</td>
                                        <td>{{ $user->created_at }}</td>
                                        <td>
                                            <a href="" title="{{ __('app.edit') }}">
                                                <i class="fa fa-edit mr-2"></i>
                                            </a>
                                            <a href="" title="{{ __('app.delete') }}">
                                                <i class="fa fa-trash text-danger"></i>
                                            </a>
                                        </td>
                                    </tr>            
                                @endforeach                
                            </tbody>                      
                        </table>
                    </div>
                </div>            
            </div>
            <!-- /.col-md-12 -->
            
        </div>
        <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
    <!-- Modal -->
    <div class="modal fade" id="form" tabindex="-1" aria-labelledby="ModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <form autocomplete="off" wire:submit.prevent="createUser">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ModalLabel">{{ __('user.addNew') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            @foreach (trans('user.nameFields') as $field => $translate) 
                            <div class="col">
                                <div class="input-group mb-3">
                                    <input type="text" wire:model.defer="state.{{$field}}" name="{{$field}}" class="form-control @error($field) is-invalid @enderror" id="Input{{$field}}" placeholder="{{ $translate }}">
                                    <div class="input-group-append">
                                        <div class="input-group-text">
                                        <span class="fas fa-user"></span>
                                        </div>
                                    </div>
                                    @error($field)<div class="invalid-feedback" role="alert">{{$message}}</div>@enderror
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="input-group mb-3">
                                    <input type="email" wire:model.defer="state.email" name="email" class="form-control @error('email') is-invalid @enderror" id="InputEmail" placeholder="{{ __('user.email') }}">
                                    <div class="input-group-append">
                                        <div class="input-group-text">
                                        <span class="fas fa-envelope"></span>
                                        </div>
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback">
                                            {{ __($message) }}.
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group mb-3">
                                    <input type="text" wire:model.defer="state.phone" name="phone" class="form-control @error('phone') is-invalid @enderror" id="InputPhone" placeholder="{{ __('user.phone') }}">
                                    <div class="input-group-append">
                                        <div class="input-group-text">
                                        <span class="fas fa-phone"></span>
                                        </div>
                                    </div>
                                    @error('phone')
                                        <div class="invalid-feedback">
                                            {{ __($message) }}.
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-group row">
                                    <label for="inputRole" class="col-sm-4 col-form-label">{{__('app.userRole')}}</label>
                                    <div class="col-sm-8">
                                        <select name="role" wire:model.defer="state.role" id="inputRole" class="form-control">
                                            @foreach (trans('roles') as $field => $translate) 
                                                <option value="{{$field}}">{{$translate}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('app.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>