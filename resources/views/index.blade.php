@extends ('layouts.master')

@section ('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <!-- Latest Activity -->
            <div class="card">

                <div class="card-header">
                    <h4 class>Better Humble Brags</h4>
                </div>

                <ul class="list-group list-group-flush">

                    <li class="list-group-item grid-bg">
                        Brag Description: <strong>{{ $brag->description }}</strong>
                    </li>

                </ul>

                <div class="card-body">
                        <form method="POST" action="/" novalidate>
                            {{ csrf_field() }}

                            <div class="form-group row">
                                <div class="col-md-8">
                                    Custom Brag Text:
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-12">
                                    <textarea class="form-control" id="comment" name="comment">{{ old('comment') }}</textarea>
                                    {{-- <input type="text" class="form-control" id="comment" name="comment" value="{{ old('comment') }}" /> --}}
                                </div>
                            </div>

                            <input type="number" class="form-control" id="id" name="id" value="{{ $brag->id }}" hidden />

                            <div class="form-group row mb-0">
                                <div class="col-md-8">
                                    <button type="submit" class="btn btn-primary">
                                        Submit
                                    </button>

                                    <a class="btn btn-primary" href="/">Refresh</a>
                                </div>
                            </div>

                        </form>

                </div>

            </div><!-- card -->

        </div><!-- col-md-8 -->
    </div><!-- row -->
</div><!-- container -->

<br />

@endsection
