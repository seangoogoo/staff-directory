<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StaffController extends Controller
{
    /**
     * Display the admin dashboard with all staff members.
     */
    public function adminDashboard()
    {
        $staff = Staff::all();
        return view('admin.dashboard', compact('staff'));
    }

    /**
     * Display the form to create a new staff member.
     */
    public function create()
    {
        return view('admin.create');
    }

    /**
     * Store a newly created staff member in the database.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'jobTitle' => 'required|string|max:100',
            'profilePicture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.create')
                ->withErrors($validator)
                ->withInput();
        }

        $staffData = $request->except('profilePicture');
        
        // Handle profile picture upload
        if ($request->hasFile('profilePicture') && $request->file('profilePicture')->isValid()) {
            $file = $request->file('profilePicture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/profiles'), $filename);
            $staffData['profilePicture'] = 'uploads/profiles/' . $filename;
        }

        Staff::create($staffData);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Staff member added successfully!');
    }

    /**
     * Display the form to edit a staff member.
     */
    public function edit($id)
    {
        $staff = Staff::findOrFail($id);
        return view('admin.edit', compact('staff'));
    }

    /**
     * Update the specified staff member in the database.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'jobTitle' => 'required|string|max:100',
            'profilePicture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'removeProfilePicture' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $staff = Staff::findOrFail($id);
        $staffData = $request->except(['profilePicture', 'removeProfilePicture']);
        
        // Check if the remove profile picture checkbox is checked
        if ($request->has('removeProfilePicture') && $request->removeProfilePicture == 1) {
            // Delete the profile picture if it exists
            if ($staff->profilePicture && file_exists(public_path($staff->profilePicture))) {
                unlink(public_path($staff->profilePicture));
            }
            // Set profilePicture to empty string
            $staffData['profilePicture'] = '';
        }
        // Handle profile picture upload (only if not removing)
        elseif ($request->hasFile('profilePicture') && $request->file('profilePicture')->isValid()) {
            // Delete the old profile picture if it exists
            if ($staff->profilePicture && file_exists(public_path($staff->profilePicture))) {
                unlink(public_path($staff->profilePicture));
            }
            
            $file = $request->file('profilePicture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/profiles'), $filename);
            $staffData['profilePicture'] = 'uploads/profiles/' . $filename;
        }

        $staff->update($staffData);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Staff member updated successfully!');
    }

    /**
     * Remove the specified staff member from the database.
     */
    public function destroy($id)
    {
        $staff = Staff::findOrFail($id);
        
        // Delete the profile picture if it exists
        if ($staff->profilePicture && file_exists(public_path($staff->profilePicture))) {
            unlink(public_path($staff->profilePicture));
        }
        
        $staff->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Staff member deleted successfully!');
    }

    /**
     * Display all staff members in the public view.
     */
    public function index()
    {
        $staff = Staff::all();
        return view('staff.index', compact('staff'));
    }
}
