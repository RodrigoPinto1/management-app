<?php

namespace App\Http\Controllers;

use App\Models\CalendarAction;
use App\Models\CalendarEvent;
use App\Models\CalendarType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CalendarController extends Controller
{
    // Render the Inertia calendar page
    public function index()
    {
        return Inertia::render('calendar/Index');
    }

    // Return events for FullCalendar (or simple list)
    public function events(Request $request)
    {
        $query = CalendarEvent::query()->with(['entity', 'type', 'action', 'user']);

        // Optional filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }
        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->query('entity_id'));
        }

        $events = $query->orderBy('start_at')->get();

        // Map to FullCalendar simple format
        $mapped = $events->map(function ($e) {
            return [
                'id' => $e->id,
                'title' => ($e->type?->name ? $e->type->name . ' - ' : '') . ($e->entity?->name ?? $e->description ?? 'Evento'),
                'start' => $e->start_at->toIso8601String(),
                'end' => $e->end_at ? $e->end_at->toIso8601String() : null,
                'allDay' => false,
                'extendedProps' => [
                    'entity' => $e->entity,
                    'type' => $e->type,
                    'action' => $e->action,
                    'description' => $e->description,
                    'state' => $e->state,
                    'user' => $e->user,
                ],
            ];
        });

        return response()->json($mapped);
    }

    // Create an event (Laravel handles validation)
    public function store(Request $request)
    {
        $data = $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'duration_minutes' => 'nullable|integer|min:1',
            'shared' => 'boolean',
            'knowledge' => 'nullable|string|max:255',
            'entity_id' => 'nullable|integer|exists:entities,id',
            'calendar_type_id' => 'nullable|integer|exists:calendar_types,id',
            'calendar_action_id' => 'nullable|integer|exists:calendar_actions,id',
            'description' => 'nullable|string',
            'state' => 'nullable|string|in:planned,confirmed,done,cancelled',
        ]);

        $data['user_id'] = Auth::id();

        // If duration provided and end_at missing, compute end_at
        if (!empty($data['duration_minutes']) && empty($data['end_at'])) {
            $data['end_at'] = now()->parse($data['start_at'])->addMinutes($data['duration_minutes']);
        }

        $event = CalendarEvent::create($data);

        // Log the activity
        activity('calendário')
            ->causedBy(auth()->user())
            ->performedOn($event)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('Criou evento no calendário');

        // If this request is an Inertia visit, return an Inertia-friendly redirect
        if ($request->header('X-Inertia')) {
            return redirect()->route('calendar');
        }

        return response()->json($event);
    }

    public function update(Request $request, CalendarEvent $calendarEvent)
    {
        $data = $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'duration_minutes' => 'nullable|integer|min:1',
            'shared' => 'boolean',
            'knowledge' => 'nullable|string|max:255',
            'entity_id' => 'nullable|integer|exists:entities,id',
            'calendar_type_id' => 'nullable|integer|exists:calendar_types,id',
            'calendar_action_id' => 'nullable|integer|exists:calendar_actions,id',
            'description' => 'nullable|string',
            'state' => 'nullable|string|in:planned,confirmed,done,cancelled',
        ]);

        $calendarEvent->update($data);

        // Log the activity
        activity('calendário')
            ->causedBy(auth()->user())
            ->performedOn($calendarEvent)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('Atualizou evento no calendário');

        if ($request->header('X-Inertia')) {
            return redirect()->route('calendar');
        }

        return response()->json($calendarEvent);
    }

    public function destroy(Request $request, CalendarEvent $calendarEvent)
    {
        // Log the activity before deletion
        activity('calendário')
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'event_id' => $calendarEvent->id,
            ])
            ->log('Eliminou evento no calendário');

        $calendarEvent->delete();

        if ($request->header('X-Inertia')) {
            return redirect()->route('calendar');
        }

        return response()->json(['deleted' => true]);
    }

    // List types and actions for select inputs
    public function types()
    {
        $types = CalendarType::where('is_active', true)->orderBy('name')->get();

        // If this is an Inertia visit, render a page; otherwise return JSON for API/fetch
        if (request()->header('X-Inertia')) {
            return Inertia::render('calendar/Types', [
                'types' => $types,
            ]);
        }

        return response()->json(['data' => $types]);
    }

    public function actions()
    {
        $actions = CalendarAction::where('is_active', true)->orderBy('name')->get();

        if (request()->header('X-Inertia')) {
            return Inertia::render('calendar/Actions', [
                'actions' => $actions,
            ]);
        }

        return response()->json(['data' => $actions]);
    }
}
