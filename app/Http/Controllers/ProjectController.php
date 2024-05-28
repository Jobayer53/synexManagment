<?php

namespace App\Http\Controllers;



use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $projects = Project::orderBy('id', 'desc')->get();
       return view('dashboard.project.projectlist',[
           'projects' => $projects,
       ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = User::has('employees')->pluck('name', 'id')->toArray();
        $client = Client::all();
        return view('dashboard.project.create',[
            'client' => $client,
            'employees' => $employees,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //project file updload
        if ($request->has('file')) {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:20000', // example validation, adjust as needed
            ]);
            if ($validator->fails()) {
                    $errors = $validator->errors();
                    foreach ($errors->messages() as  $messages) {
                        foreach ($messages as $message) {
                            flash()->options([
                                'position' => 'bottom-right',
                            ])->error($message);
                        }
                    }
                return back()->withErrors($validator)->withInput();
            }
            $project = Project::find($request->project_id);
            $file = $request->file('file');
            $explode = explode('.', $file->getClientOriginalName());
            $filename = $explode[0] . '_' . time() . '.' . $explode[1];
            $file -> move(public_path('uploads/project/file/'), $filename);
            $fileArray = json_decode($project->file, true) ?? [];
            $fileArray[] = $filename;
            $project->file = json_encode($fileArray);
            $project->save();
            flash()->options(['position' => 'bottom-right'])-> success('File Added successfully');
            return back();
        }



        //project data upload
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'client_id' => 'required',
            'daterange' => 'required',
            'budget'    => 'required',
            'leader' => 'required',
            'member' => 'required',
            ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            foreach ($errors->messages() as  $messages) {
                foreach ($messages as $message) {
                    flash()->options([
                        'position' => 'bottom-right',
                    ])->error($message);
                }
            }
            return back()->withErrors($validator)->withInput();
        }

        $project = new Project();
        $project->name = $request->name;
        $project->client_id = $request->client_id;
        $project->budget = $request->budget;
        $project->description = $request->description;
        $project->dateRange = $request->daterange;
        $project->status = $request->status;
        $project->priority = $request->priority;
        $project->leader_id = $request->leader;
        $memberIds = implode(',', $request->member);
        $project->member_id = $memberIds;
        $project->save();
        flash()->options(['position' => 'bottom-right'])-> success('Project created successfully');
        return redirect()->route('project.show', $project->id);




    }

    /**
     * Display the specified resource.
     */
    public function show(String $id)
    {
        $project = Project::find($id);

        //project file
        $files = json_decode($project->file, true) ?? [];
        //members
        $memberIds = explode(',', $project->member_id);
        $memberCount = User::whereIn('id', $memberIds)->pluck('name')->count();
        $members = User::whereIn('id', $memberIds)->get();



        return view('dashboard.project.project_overview',[
            'project' => $project,
            'members' => $members,
            'memberCount' => $memberCount,
            'files' => $files,


        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $project = Project::find($id);
        dd( $project);
        $fileArray = json_decode($project->file, true) ?? [];


    }
    public function fileDelete($id , $key){
      $project = Project::find($id);
      $fileArray = json_decode($project->file, true) ?? [];
      if (isset($fileArray[$key])) {
        $filename = $fileArray[$key];
        unlink(public_path('uploads/project/file/' . $filename));
        unset($fileArray[$key]);
        $project->file = json_encode($fileArray);
        $project->save();
        flash()->options(['position' => 'bottom-right'])-> success('File Deleted');
        return back();
        }
        flash()->options(['position' => 'bottom-right'])-> error('File not found');
        return back();

    }
    public function downloadFile($filename){
        // Assuming your files are stored in the storage/app/public directory
        $filePath = public_path('uploads/project/file/' . $filename);

        // Check if the file exists
        if (file_exists($filePath)) {
            // Return the file for download with appropriate headers

            return response()->download($filePath);
        } else {
            // Return a response if the file does not exist
            flash()->options(['position' => 'bottom-right'])-> error('File not found');
            return back();
        }
    }


}
