<?php
namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;

class ImportController extends Controller
{
    public function importXML(Request $request)
    {
        $request->validate(['xml_file' => 'required|file|mimes:xml']);

        $xmlData = simplexml_load_file($request->file('xml_file'));
        $successCount = 0;
        $duplicateCount = 0;
        $updatedCount = 0;
        $errors = [];

        foreach ($xmlData->contact as $contact) {
            $phone = isset($contact->phone) ? trim((string) $contact->phone) : null;
            $name = isset($contact->name) ? trim((string) $contact->name) : null;
            $lastName = isset($contact->lastName) ? trim((string) $contact->lastName) : null;
            if (!$phone || !$name || !$lastName) {
                $errors[] = "Missing data for contact: " . json_encode($contact);
                continue;
            }
            $phone = preg_replace('/\s+/', ' ', $phone);
            if (!preg_match('/^\+90 \d{3} \d{7}$/', $phone)) {
                $errors[] = "Invalid phone number format for contact: " . json_encode($contact);
                continue;
            }

            try {
                $existingContact = Contact::where('phone', $phone)->first();
                if ($existingContact) {
                    $existingContact->update([
                        'name' => $name,
                        'last_name' => $lastName
                    ]);
                    $updatedCount++;
                } else {
                    Contact::create([
                        'phone' => $phone,
                        'name' => $name,
                        'last_name' => $lastName,
                    ]);
                    $successCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Error processing contact: " . json_encode($contact) . " - " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Import completed',
            'successCount' => $successCount,
            'updatedCount' => $updatedCount,
            'duplicateCount' => $duplicateCount,
            'errors' => $errors
        ]);
    }

    public function updateContact(Request $request, $id)
    {
        try {

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => [
                    'required',
                    'string',
                    'regex:/^\+90 \d{3} \d{7}$/'
                ],
            ], [
                'name.required' => 'The first name is required.',
                'last_name.required' => 'The last name is required.',
                'phone.required' => 'The phone number is required.',
                'phone.regex' => 'The phone number format must be +90 XXX XXXXXXX.'
            ]);

            $contact = Contact::findOrFail($id);
            $existingContact = Contact::where('phone', $validatedData['phone'])
                ->where('id', '!=', $id)
                ->first();

            if ($existingContact) {
                return response()->json([
                    'errors' => ['phone' => ['This phone number is already in use.']]
                ], 422);
            }
            $contact->update($validatedData);
            return response()->json(['message' => 'Contact updated successfully!']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Contact not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the contact.'], 500);
        }
    }

    public function getContacts(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');

        $contacts = Contact::query();
        if (!empty($search)) {
            $contacts->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");

                if (str_contains($search, ' ')) {
                    $nameParts = explode(' ', $search, 2);
                    if (count($nameParts) == 2) {
                        $query->orWhere(function ($subQuery) use ($nameParts) {
                            $subQuery->where('name', 'like', "%{$nameParts[0]}%")
                                ->where('last_name', 'like', "%{$nameParts[1]}%");
                        });
                    }
                }
            });
        }
        $contacts = $contacts->orderByDesc('id')->paginate($perPage)->through(function ($contact) {
            return [
                'id' => $contact->id,
                'full_name' => "{$contact->name} {$contact->last_name}",
                'phone' => $contact->phone,
            ];
        });

        return response()->json($contacts);
    }


    public function deleteContact($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return response()->json(['message' => 'Contact deleted successfully!']);
    }


}
