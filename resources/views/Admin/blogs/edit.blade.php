@extends('layouts.admin_app')
@section('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection
@section('content')
<div class="content-header row">
 <div class="content-header-light col-12">
  <div class="row">
   <div class="content-header-left col-md-9 col-12 mb-2">
    <h3 class="content-header-title">Blog Edit Dashboard</h3>
    <div class="row breadcrumbs-top">
     <div class="breadcrumb-wrapper col-12">
      <ol class="breadcrumb">
       <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a>
       </li>
       <li class="breadcrumb-item"><a href="{{ url('/admin/blogs/') }}">Back To Blogs</a>
       </li>
       <li class="breadcrumb-item active">Blog Edit Dashboard
       </li>
      </ol>
     </div>
    </div>
   </div>
   <div class="content-header-right col-md-3 col-12">
    <div class="btn-group float-md-right" role="group" aria-label="Button group with nested dropdown">
     <button class="btn btn-primary round dropdown-toggle dropdown-menu-right box-shadow-2 px-2 mb-1" id="btnGroupDrop1"
      type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
     <div class="dropdown-menu"><a class="dropdown-item" href="component-alerts.html"> Alerts</a><a
       class="dropdown-item" href="material-component-cards.html"> Cards</a><a class="dropdown-item"
       href="component-progress.html"> Progress</a>
      <div class="dropdown-divider"></div><a class="dropdown-item" href="register-with-bg-image.html"> Register</a>
     </div>
    </div>
   </div>
  </div>
 </div>
</div>
<div class="content-overlay"></div>

<!-- Configuration option table -->
<section id="configuration">
 <div class="row">
  <div class="col-12">
   <div class="card">
    <div class="card-header">
     <h4 class="card-title">
      <a href="{{ url('/admin/blogs/') }}" class="btn btn-success btn-round">Back To Blogs</a>
     </h4>
     <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
     <div class="heading-elements">
      <ul class="list-inline mb-0">
       <li><a data-action="collapse"><i class="ft-minus"></i></a></li>
       <li><a data-action="reload"><i class="ft-rotate-cw"></i></a></li>
       <li><a data-action="expand"><i class="ft-maximize"></i></a></li>
       <li><a data-action="close"><i class="ft-x"></i></a></li>
      </ul>
     </div>
    </div>
    <div class="card-content collapse show">
     <div class="card-body card-dashboard">
      <div class="row justify-content-md-center">
       <div class="col-md-8">
        <div class="card">
         <div class="card-header">
          <h4 class="card-title" id="from-actions-top-bottom-center">Blog Edit</h4>
          <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
          <div class="heading-elements">
           <ul class="list-inline mb-0">
            <li><a data-action="collapse"><i class="ft-minus"></i></a></li>
            <li><a data-action="reload"><i class="ft-rotate-cw"></i></a></li>
            <li><a data-action="expand"><i class="ft-maximize"></i></a></li>
            <li><a data-action="close"><i class="ft-x"></i></a></li>
           </ul>
          </div>
         </div>
         <div class="card-content collpase show">
          <div class="card-body">


           <form class="form" method="post" action="{{ url('/admin/blogs/edit/'.$blog->id) }}" enctype="multipart/form-data">
            @csrf
            <div class="form-body">
             <div class="row">
                <div class="form-group col-12 mb-2">
                    <label for="eventInput2">Multimedia</label>
                    <input type="file" id="eventInput2" class="form-control" placeholder="Role Name" name="medias[]" multiple>

                </div>
                <div class="form-group col-12 mb-2">
                <label for="eventInput2">Description</label>
                <textarea name="description" cols="30" rows="10" class="form-control" placeholder="Enter Description">{!! $blog->description !!}</textarea>
                @error('description')
                <span class="text-danger">*{{ $message }}</span>
                @enderror
                </div>
             </div>
            </div>

            <div class="form-actions text-center">
             <button type="button" class="btn btn-warning mr-1">
              <i class="ft-x"></i> Cancel
             </button>
             <button type="submit" class="btn btn-primary">
              <i class="la la-check-square-o"></i> Save
             </button>
            </div>
           </form>
           <table class="table">
            <thead>
                <tr>
                    <th>Media</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($blog->medias as $media)
                <tr>
                    <td>
                        @php
                            $url = $media->media_url;
                            $path = parse_url($url, PHP_URL_PATH);
                            $pathInfo = pathinfo($path);
                            $ext = $pathInfo['extension'];
                        @endphp
                        @if ($ext == "mp4")
                        <video src="{{ $media->media_url }}" width="100px" controls></video>
                        @else
                        <img src="{{ $media->media_url }}" width="100px" class="img-thumbnail" alt="">
                        @endif
                    </td>
                    <td>
                        <div class="d-flex">
                            <form action="{{ url('/admin/blogs/media/'.$media->id) }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="d-flex">
                                    <input type="file" name="media" class="form-control">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-pen-to-square"></i></button>
                                </div>
                            </form>
                            <div>
                                <form action="{{ url('/admin/blogs/media/delete/'.$media->id) }}" method="post">
                                    @csrf
                                    <button class="btn btn-sm btn-danger py-1 ml-1" type="subit"><i class="fas fa-trash"></i></button>
                                </form>
                                {{-- <a href="" class="btn btn-sm btn-danger py-1 ml-1"><i class="fas fa-trash"></i></a> --}}
                            </div>

                        </div>


                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
          </div>
         </div>
        </div>
       </div>
      </div>

     </div>
    </div>
   </div>
  </div>
 </div>
 @include('sweetalert::alert')

</section>
<!--/ Configuration option table -->
@endsection

@section('scripts')
<!-- <script src="{{ asset('admin_app/app-assets/vendors/js/material-vendors.min.js') }}"></script> -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
 $('.select2').select2({
  multiple: true
 });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    $('#desc').summernote({
      placeholder: 'Enter Blog Description',
      tabsize: 2,
      height: 120,
      toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'underline', 'clear']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        // ['insert', ['link', 'picture', 'video']],
        ['view', ['fullscreen', 'codeview', 'help']]
      ]
    });
</script>

@endsection
